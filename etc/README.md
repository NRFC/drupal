# Extra config

```
etc
├── apache.conf
├── ansible
├── config-sync
└── docker
```

## apache.conf

The site config file to set up apache on a dev machine.

### Debian/Ubuntu

Assuming you have PHP 8.x and the other requirements set up to run a drupal/symfony site, ececute these from the repo root.

 * `sed "s,REPO_ROOT,$(pwd)/web,g" etc/apache.conf | sudo tee /etc/apache2/sites-available/004-dev-nrfc.conf`
 * `sudo a2ensite 004-dev-nrfc.conf`
 * `sudo systemctl restart apache2`
 * `echo 127.0.0.1 www.nrfc.test | sudo tee -a /etc/hosts`

### ansible

### config-sync

### docker

The docker builder and a docker-compose.yml that brings up a deno site

```bash
docker build -t tobybatch/nrfc .
docker push tobybatch/nrfc
```

And

```bash
docker compose up -d
```

http://localhost:9900
