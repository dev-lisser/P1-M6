#!/usr/bin/python3
# scan_nmap.py


import subprocess
import re
import mysql.connector
import sys
import socket

# -------- CONFIGURATION --------
NETWORK = "PLAGE_IP"   
DB_HOST = "localhost"
DB_USER = "NOM_USER_MARIADB"
DB_PASS = "MDP_USER"
DB_NAME = "NOM_BASE_DONNES"
TABLE   = "NOM_TABLE"
# --------------------------------

def detect_gateway():
    """Récupère la passerelle par défaut via `ip route`. Retourne IP ou None."""
    try:
        out = subprocess.check_output(["ip", "route"], stderr=subprocess.DEVNULL).decode("utf-8")
        for line in out.splitlines():
            line = line.strip()
            if line.startswith("default"):
                parts = line.split()
                if "via" in parts:
                    return parts[parts.index("via") + 1]
    except Exception:
        pass
    return None

def run_nmap(network):
    """Lance nmap -sn et retourne la sortie texte (stdout)."""
    try:
        out = subprocess.check_output(["sudo", "nmap", "-sn", network, "-oN", "-"],
                                      stderr=subprocess.DEVNULL)
        return out.decode("utf-8", errors="replace")
    except subprocess.CalledProcessError as e:
        print("Erreur lors de l'exécution de nmap :", e, file=sys.stderr)
        return ""

def try_reverse_lookup(ip):
    """Essaye un reverse DNS avec socket.gethostbyaddr. Retourne nom ou None."""
    try:
        name, _, _ = socket.gethostbyaddr(ip)
        if name and name != ip:
            return name
    except Exception:
        pass
    return None

def parse_nmap(output, gw_ip):
    """
    Parse la sortie de nmap -sn.
    Retourne une liste de tuples (nom, ip, mac).
    Règles :
      - si ip == gw_ip -> nom = '_gateway'
      - sinon si nmap fournit un nom => l'utiliser
      - sinon tenter reverse lookup
      - sinon nom = 'Poste-<ip-with-dashes>'
    """
    hosts = []
    re_report_name_ip = re.compile(r'^Nmap scan report for (.+?) \((\d+\.\d+\.\d+\.\d+)\)$')
    re_report_ip = re.compile(r'^Nmap scan report for (\d+\.\d+\.\d+\.\d+)$')
    re_mac = re.compile(r'^\s*MAC Address:\s*([0-9A-Fa-f:]{17})')

    current_name = None
    current_ip = None

    for line in output.splitlines():
        line = line.strip()
        if not line:
            continue

        m = re_report_name_ip.match(line)
        if m:
            current_name = m.group(1).strip()
            current_ip = m.group(2).strip()
            continue

        m2 = re_report_ip.match(line)
        if m2:
            current_name = ""   # pas de nom fourni par nmap
            current_ip = m2.group(1).strip()
            continue

        m3 = re_mac.search(line)
        if m3 and current_ip:
            mac = m3.group(1).lower()
            ip = current_ip

            if gw_ip and ip == gw_ip:
                name = "_gateway"
            else:
                if current_name and current_name.strip():
                    name = current_name
                else:
                    # tenter reverse DNS
                    rev = try_reverse_lookup(ip)
                    if rev:
                        name = rev
                    else:
                        name = "Poste-" + ip.replace(".", "-")

            hosts.append((name, ip, mac))
            current_name = None
            current_ip = None

    return hosts

def insert_into_db(records):
    """Vide la table et insère les enregistrements (nom, ip, mac)."""
    try:
        conn = mysql.connector.connect(
            host=DB_HOST,
            user=DB_USER,
            password=DB_PASS,
            database=DB_NAME
        )
        cursor = conn.cursor()

        # vider la table
        cursor.execute(f"DELETE FROM {TABLE}")
        conn.commit()

        if records:
            sql = f"INSERT INTO {TABLE} (nom, ip, mac) VALUES (%s, %s, %s)"
            cursor.executemany(sql, records)
            conn.commit()
            print(f"{cursor.rowcount} enregistrements insérés dans {DB_NAME}.{TABLE}")
        else:
            print("Aucun enregistrement à insérer (table vidée).")

        cursor.close()
        conn.close()
    except mysql.connector.Error as err:
        print("Erreur BD :", err, file=sys.stderr)

if __name__ == "__main__":
    print(f"Détection de la passerelle...")
    gw = detect_gateway()
    print("Passerelle détectée :", gw if gw else "(non détectée)")

    print(f"Lancement du scan nmap sur {NETWORK} ... (nécessite souvent sudo)")
    out = run_nmap(NETWORK)
    hosts = parse_nmap(out, gw)

    print("Hôtes détectés (nom, ip, mac) :")
    for h in hosts:
        print(h[0], h[1], h[2])

    insert_into_db(hosts)
