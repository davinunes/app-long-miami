#!/usr/bin/python3

from paramiko import SSHClient, RSAKey
import paramiko
import sys

COMANDO = sys.argv[1]

class SSH:
    def __init__(self):
        self.ssh = SSHClient()
        self.ssh.load_system_host_keys()
        self.ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        # Substitua 'caminho_para_sua_chave.ppk' pelo caminho do seu arquivo .ppk
        self.key = RSAKey.from_private_key_file('/var/www/html/py/mykeyopenssh.pem')
        self.ssh.connect(hostname='127.0.0.1', port='22', username='root', pkey=self.key)

    def exec_cmd(self, cmd):
        stdin, stdout, stderr = self.ssh.exec_command(cmd)
        if stderr.channel.recv_exit_status() != 0:
            print(stderr.read().decode())  # Modificado para Python 3
        else:
            print(stdout.read().decode())  # Modificado para Python 3

if __name__ == '__main__':
    ssh = SSH()
    ssh.exec_cmd(COMANDO)
