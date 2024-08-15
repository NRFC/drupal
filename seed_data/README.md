# Importing data

## tldr;

```
./vendor/bin/drush nrfctu $(pwd)/seed_data/taxonomies.json
./vendor/bin/drush nrfctu $(pwd)/seed_data/people.json
```

Set up Paragraph type for teams to use, the taxonomy "team role" needs to set on the paragraph Volunteer:field_roles association.

```
./vendor/bin/drush nrfctu $(pwd)/seed_data/teams.json
```
