help:
	@egrep "^#" Makefile

# target: docker-build|db               - Setup/Build PHP & (node)JS dependencies
db: docker-build
docker-build: build-back

build-back:
	docker-compose run --rm php sh -c "composer install"

build-back-prod:
	docker-compose run --rm php sh -c "composer install --no-dev -o"

build-zip:
	cp -Ra $(PWD) /tmp/pssentry
	rm -rf /tmp/pssentry/.ddev
	rm -rf /tmp/pssentry/.env.test
	rm -rf /tmp/pssentry/.php_cs.*
	rm -rf /tmp/pssentry/.travis.yml
	rm -rf /tmp/pssentry/cloudbuild.yaml
	rm -rf /tmp/pssentry/composer.*
	rm -rf /tmp/pssentry/package.json
	rm -rf /tmp/pssentry/.npmrc
	rm -rf /tmp/pssentry/package-lock.json
	rm -rf /tmp/pssentry/.gitignore
	rm -rf /tmp/pssentry/deploy.sh
	rm -rf /tmp/pssentry/.editorconfig
	rm -rf /tmp/pssentry/.git
	rm -rf /tmp/pssentry/.github
	rm -rf /tmp/pssentry/_dev
	rm -rf /tmp/pssentry/tests
	rm -rf /tmp/pssentry/docker-compose.yml
	rm -rf /tmp/pssentry/Makefile
	rm -rf /tmp/pssentry/LICENCE
	mv -v /tmp/pssentry $(PWD)/pssentry
	zip -r pssentry.zip pssentry
	rm -rf $(PWD)/pssentry

# target: build-zip-prod                   - Launch prod zip generation of the module (will not work on windows)
build-zip-prod: build-back-prod build-zip