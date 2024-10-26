# Site backup

```
ansible-playbook -i hosts.yaml -i vault.yaml playbooks/get-site.playbook.yaml
```

```
tar zxf $(ls playbooks/backups/dev/tmp/20* -1|tail -n 1)
sudo cp -r files ../../web/sites/default
openssl aes-256-cbc -d -pbkdf2 -pass pass:$(cat .crypt-password) -in dump.sql.gz.enc | gzip -d > ../../etc/docker/initdb.d/dump.sql
rm dump*
rm -rf files
sudo chown -R 33:33 ../../web/sites/default/files/
```
