.PHONY: lang theme

pull:
	git pull
	make pc

pc: ## purge_caches ct=<cache_type>
	php admin/cli/purge_caches.php $(ct)
lang:
	php admin/cli/purge_caches.php --lang
theme:
	php admin/cli/purge_caches.php --theme
js:
	php admin/cli/purge_caches.php --js
grunt:
	grunt -f --js
	php admin/cli/purge_caches.php --js

other:
	php admin/cli/purge_caches.php --other

update:
	php admin/cli/upgrade.php --non-interactive

adhoc:
	php admin/cli/adhoc_task.php --execute