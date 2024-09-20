# Site backup

```
ansible-playbook -i hosts.yaml -i vault.yaml playbooks/get-site.playbook.yaml
```

```
openssl aes-256-cbc -d -a -in /path/to/your/encrypted_output_file -out /path/to/decrypted_output_file -pass pass:your_encryption_password
```

```
openssl aes-256-cbc -d -pbkdf2 -pass pass:$(cat .crypt-password) -in playbooks/backups/dev/var/tmp/dumps/2024-09-20T06\:47\:05Z.sql.gz.enc | gzip -d > dump.sql
```
