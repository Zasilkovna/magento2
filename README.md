[Návod v češtině](https://github.com/Zasilkovna/magento2#modul-pro-magento-2)
    
# Module for Magento 2

### Download module

[Download the latest version](https://github.com/Zasilkovna/magento2/releases/latest)

### Installation

Installation and registration of the module are done by CLI utility, which is part of Magento 2.
This utility is available in Magento installation directory as "/bin/magento".

- copy directory 'Packetery' to directory: `/app/code`
- enable module using CLI utility: `bin/magento module:enable Packetery_Checkout --clear-static-content`
- registration of module: `bin/magento setup:upgrade`
- re-deploy static content (not needed in dev mode): `bin/magento setup:static-content:deploy`
- project recompiling: `bin/magento setup:di:compile`
- clean cache: `bin/magento cache:clean`
- set Packeta configuration in administration for default scope even if Packeta carrier is inactive
- import carriers: `bin/magento packetery:import-feed-carriers`
- set up command `bin/magento cron:run` in cron, in order to update carriers regularly and run message queue consumers

## Upgrading

- set Packeta configuration in administration for default scope even if Packeta carrier is inactive
- enable maintenance mode: `bin/magento maintenance:enable`
- remove all previous source files (remove app/code/Packetery folder)
- next steps are the same as during the installation
- (optional) Migrate pricing rules from versions 2.0.1 and 2.0.2: `bin/magento packetery:migrate-price-rules`
- (optional) Migrate default price from versions up to 2.0.5: `bin/magento packetery:migrate-default-price`
- disable maintenance mode: `bin/magento maintenance:disable`
- check configuration

#### Migration of pricing rules from versions 2.0.1 and 2.0.2

Within this task, pricing rules are migrated to the following extent: for countries from the original list of rules
are transferred the rules for the variant of delivery to the pickup point, including weight ranges and the free shipping price.

Furthermore, the maximum weight and free shipping price valid for the entire module are transferred.

Pricing rules are created as unavailable.

As of version 2.0.3, it is not necessary to perform.

#### Migration of default price from versions up to 2.0.5

Within this task, the default price is migrated only if specific countries are selected in the global settings of the module.

If all countries are selected, migration is not performed.

Price rules are created as unavailable, without set maximum weight.

### Development

#### PHP Compatibility Checks

The module includes PHPCompatibility checks to ensure compatibility with PHP 8.1 through PHP 8.5.

Requirements to run the checks: PHP 8.4 or newer and Composer 2.8.6 or newer. From the repository root (where the top-level `composer.json` lives), run:

```bash
composer install
composer phpcs-compatibility:81  # Check PHP 8.1 compatibility
composer phpcs-compatibility:84  # Check PHP 8.4 compatibility
composer phpcs-compatibility:85  # Check PHP 8.5 compatibility
```

These commands use PHP_CodeSniffer with the PHPCompatibility standard to detect compatibility issues.

#### Marketplace (EQP) checks

The module must permanently pass the blocking checks of the Magento Marketplace Extension Quality Program (EQP). All checks below run locally from the repository root and need PHP 8.4 or newer and Composer 2.8.6 or newer, the same as the compatibility checks above — except the malware scan, which needs YARA and ClamAV instead; the code sniffer and copy-paste detector use the same version constraints and parameters as CI (`.github/workflows/marketplace-checks.yml`).

First install the dev dependencies (this only installs the tools, it runs no checks):

```bash
composer install
```

This also installs the marketplace tools from `tools/composer.json` into `tools/vendor`.

**Code sniffer** (blocking; Magento2 standard, only severity 10 errors):

```bash
composer phpcs-magento2
```

**Copy-paste detector** (advisory — findings must be assessed manually; duplications of Magento core or other extensions are blocking for EQP, the tool also produces false positives):

```bash
composer phpcpd
```

It exits with a non-zero status on any finding, including a harmless one — the output is meant for review, not for a blocking gate.

**Copy-paste detector against Magento core** (advisory for now — the CI job reports a finding without failing the run; this is the duplication EQP rejects, the check above compares the module only with itself):

```bash
composer phpcpd-core -- /path/to/magento/vendor/magento
# without an installation at hand, a sparse clone of the PHP sources is enough (~17 s, 112 MB)
git clone --depth 1 --branch 2.4.9 --filter=blob:none --sparse https://github.com/magento/magento2.git /tmp/magento-core
git -C /tmp/magento-core sparse-checkout set app/code/Magento lib/internal/Magento
composer phpcpd-core -- /tmp/magento-core/app/code/Magento /tmp/magento-core/lib/internal/Magento
```

⚠️ **Performance warning (Windows):** exclude the core checkout from the folders your antivirus scans (usually Windows Defender), otherwise the first run over a fresh checkout can take tens of minutes.

Only clones crossing the module boundary are reported, so a finding always means shared code. Takes about 20 seconds against the clone and 35 seconds against `vendor/magento`; the script raises `memory_limit` itself, the run peaks around 1.5 GB.

**Package verification** (valid manifest, the manifest rules Adobe lists for extension packages, manifest version equal to the version starting the first line of `CHANGE_LOG.txt`, package zip under 30 MB, no TODO/FIXME markers outside `/Test/`):

```bash
composer validate Packetery/Checkout/composer.json
composer verify-manifest
php -r 'echo json_decode(file_get_contents("Packetery/Checkout/composer.json"))->version, PHP_EOL;' && head -1 CHANGE_LOG.txt
(cd Packetery/Checkout && set -o pipefail && zip -rq - . -x '.git/*' | wc -c)   # bytes, limit 31457280
grep -rIniE '\b(TODO|FIXME)\b' --include='*.php' --include='*.phtml' --include='*.xml' --include='*.js' Packetery/Checkout | grep -v '/Test/' || echo 'OK: no TODO/FIXME'
```

`composer verify-manifest` reads the manifest and checks the rules from the Adobe technical review guidelines: declared name, type and version, allowed package type, no `extra.map` or `extra.magento-root-dir`, no dependency on the Magento base packages, no `*` constraint on `magento/*`, no require inline aliases, `registration.php` in `autoload.files` and a namespace in `autoload.psr-4`, plus `registration.php` and a parseable `etc/module.xml`. It also compares the `php` constraint with the version matrix in `.github/workflows/marketplace-checks.yml`, because Adobe rejects a package that narrows the PHP versions its supported Magento versions allow.

**PHP lint**:

```bash
find Packetery/Checkout -name '*.php' -not -path '*/vendor/*' -print0 | xargs -0 -n1 -P4 php -l >/dev/null
```

**PHP compatibility** — see the PHP Compatibility Checks section above.

**Malware scan** (CI covers it too, as the `malware-scan` job; Adobe runs the final scan within EQP, this is a best-effort pre-check). Two tools are used, mirroring Adobe: ClamAV antivirus and YARA with a community ruleset for PHP malware/webshells.

```bash
composer malware-scan
```

`bin/malware-scan` is what the CI job runs as well: it fetches the YARA ruleset at the commit pinned at the top of the script, scans with it, then runs ClamAV, and fails on a finding as well as on a file it could not read. It only scans — both tools have to be installed first and the ClamAV signature database has to be current, because `freshclam` needs root and stays outside the script.

For ClamAV alone the quickest way is Docker — identical on every OS, no local install, the image ships an up-to-date signature database:

```bash
docker run --rm -v "$PWD/Packetery/Checkout:/scan:ro" clamav/clamav:stable clamscan -r /scan
```

On ARM machines (Apple Silicon, ARM Linux) add `--platform linux/amd64` to the command — the image has no ARM build, so without it Docker fails on a missing manifest.

The script needs both tools installed natively:

- macOS: `brew install yara clamav`. ClamAV then needs its config file created, otherwise `freshclam` fails with `Can't open/parse the config file`: `cp "$(brew --prefix)/etc/clamav/freshclam.conf.sample" "$(brew --prefix)/etc/clamav/freshclam.conf"` and comment out the `Example` line in it. Use the Docker command above if you want to skip this setup.
- Linux: `sudo apt install yara clamav` (Debian/Ubuntu), `sudo dnf install yara clamav clamav-update` (Fedora)
- Windows: local development typically runs inside WSL2 (Ubuntu) or a Docker container — follow the Linux instructions there. A native setup is possible too: `choco install yara clamav`, or the official binaries (YARA: [github.com/VirusTotal/yara/releases](https://github.com/VirusTotal/yara/releases), ClamAV: [clamav.net/downloads](https://www.clamav.net/downloads))

ClamAV — update the signature database, then scan the module directory:

```bash
freshclam
clamscan -r Packetery/Checkout
```

YARA — with the [php-malware-finder](https://github.com/jvoisin/php-malware-finder) community ruleset:

```bash
git clone https://github.com/jvoisin/php-malware-finder /tmp/php-malware-finder
yara -w -r /tmp/php-malware-finder/data/php.yar Packetery/Checkout
```

Notes:

- All commands in this section expect bash or zsh (the package zip check uses `set -o pipefail`); on Windows run them in WSL2 (or Git Bash — the package zip check additionally needs the `zip` utility, which Git Bash does not bundle; in WSL2 install it via `apt install zip`).
- The copy-paste detector against Magento core needs WSL2 on Windows, Git Bash is not enough: a native Windows PHP cannot open the `/c/…` paths Git Bash produces, and the shell rewrites a Windows path back to that form. The script asks the scanning PHP whether it sees the files, so this ends as a loud failure, not as a clean run.
- Windows clones created before `bin/* text eol=lf` was added keep their CRLF copies — switching branches does not rewrite a file whose content did not change — and `composer phpcs-compatibility:*` then fails with `ERROR: The file "Packetery/Checkout" does not exist`. Refresh the helper once with `rm bin/phpcs-compatibility && git checkout -- bin/phpcs-compatibility`.
- Debian/Ubuntu packages pull in the `clamav-freshclam` service, which keeps the signature database up to date on its own — running `freshclam` by hand is usually unnecessary there. If `freshclam` complains about a missing configuration (typical for Homebrew and the Windows builds), create `freshclam.conf` from the bundled `freshclam.conf.sample` and comment out the `Example` line.
- Clone the YARA ruleset outside the repository — it carries a `samples/` directory with live webshells, which can also trip antivirus or endpoint protection on managed machines. To skip them entirely, fetch only the rules: `git clone --filter=blob:none --sparse https://github.com/jvoisin/php-malware-finder /tmp/php-malware-finder && git -C /tmp/php-malware-finder sparse-checkout set data`, then delete `data/samples`. `composer malware-scan` does exactly this on its own, in a temporary directory.
- `--depth 1` does not belong in that clone: a shallow clone carries only the tip of the default branch, so `git checkout` of the pinned commit fails in it with `fatal: reference is not a tree` as soon as the ruleset moves on past the pin.
- CI and a local `composer malware-scan` scan with the same rules, because the pin lives in `YARA_RULES_COMMIT` at the top of `bin/malware-scan` — changing the rules is a commit visible in a pull request. ClamAV is deliberately not pinned and refreshes its database on every run, because a fresh database is what an antivirus is for. Running `yara` by hand against a fresh clone therefore uses newer rules than CI; to reproduce a CI finding, check out the commit named in the script.
- `composer install` in the repository root also installs the marketplace tools into `tools/`, so it needs network access even when you only want the module dependencies. Behind a proxy or offline it fails on that step with the root `vendor/` already in place; rerun it with network, or use `composer install --no-scripts` and install the tools later with `composer install --working-dir=tools`.

#### Message queue consumer (bulk packet submission)

Bulk packet submission from the order list is processed asynchronously by Magento consumer:

- `packetery.checkout.packet.submit`

To run consumers via Magento cron runner, configure `app/etc/env.php`:

```php
'queue' => [
    'consumers_wait_for_messages' => 0
],
'cron_consumers_runner' => [
    'cron_run' => true,
    'max_messages' => 200
]
```

If you prefer to run only this consumer via cron runner, use:

```php
'cron_consumers_runner' => [
    'cron_run' => true,
    'max_messages' => 200,
    'consumers' => [
        'packetery.checkout.packet.submit'
    ]
]
```

If consumers are not started by `bin/magento cron:run`, start the packet submission consumer manually:

```bash
bin/magento queue:consumers:start packetery.checkout.packet.submit
```

#### Packet status tracking

Submitted packets have their current Packeta status fetched periodically and shown in the order grid (column `Packet status`). A cron job selects the packets that still need updating (the longest-unsynchronized first) and hands them to a consumer that performs the actual API call:

- consumer: `packetery.checkout.packet.status.sync`
- cron job: `syncPacketStatus` (group `packetery`), no committed schedule — runs only once devops sets a `cron_expr`

The consumer is started the same way as the bulk submission consumer (via `cron_consumers_runner` in `app/etc/env.php`, or manually):

```bash
bin/magento queue:consumers:start packetery.checkout.packet.status.sync
```

The following are deployment (devops) concerns, not merchant admin options — they affect server and message-queue load, so none of them is exposed in the admin UI. The `status_polling/batch_size` key lives in the `packetery/status_polling` array in `app/etc/env.php`:

```php
'packetery' => [
    'status_polling' => [
        'batch_size' => 200   // omit or 0 = no limit
    ]
]
```

- **Batch size per run** — `status_polling/batch_size`. Maximum packets the cron queues per run; absent or `0` means no limit (all open packets are queued).
- **Enable / disable + frequency** — the cron config path `crontab/packetery/jobs/syncPacketStatus/schedule/cron_expr` (`packetery` = the job's cron group). The job has no committed default schedule, so this value is the single switch: setting it enables the feature and defines how often it runs, clearing/unsetting it disables it (the feature is off right after install). It has no admin field, so set it in `app/etc/config.php` under the `system/default` tree (not via `bin/magento config:set`, which only accepts `system.xml` paths):

```php
'system' => [
    'default' => [             // config scope
        'crontab' => [
            'packetery' => [   // cron group
                'jobs' => [
                    'syncPacketStatus' => [
                        'schedule' => ['cron_expr' => '17 */3 * * *']
                    ]
                ]
            ]
        ]
    ]
]
```

  Then `bin/magento cache:flush config`.

#### API action log retention

Packeta API actions (submit, cancel, label print, packet list print) performed in the admin are recorded
in the **Packeta → Log** grid. A daily cron job (`packetery_log_retention`, `Packetery\Checkout\Cron\LogPurger`)
deletes records older than a configured number of days.

The retention period is a devops operational lever, not an admin setting, so it lives in the deploy
configuration (`app/etc/env.php` or `app/etc/config.php`):

```php
'packetery' => [
    'log' => [
        'retention_days' => 30
    ]
]
```

Behaviour: unset defaults to `30`; a positive integer `N` deletes records older than `N` days daily;
`0` or a negative value disables purging. The cron runs in the `packetery` group at `0 2 * * *`.

### Configuration and "How to" guide

### Information about the module

#### Supported languages:

- czech
- english

#### Supported versions:

- Magento 2.4.4+ (including 2.4.9)
- php 8.1 - 8.5
- If you have a problem using the module, please contact us by email: [e-commerce.support@packeta.com](mailto:e-commerce.support@packeta.com)

#### Supported features:

- integration of widget v6 in the cart
- support for external carriers' pickup points
- delivery to an address via external carriers
- setting different prices for each carrier
- free shipping from the specified price
- possibility of bulk weight adjustment in the shipment list
- possibility of bulk packet submission from the order list (asynchronous processing via message queue consumer)
- export shipments to a CSV file that can be imported in [client section](https://client.packeta.com/)
- possibility to change the pickup point for an existing order in the administration
- email template variables

#### Email template variables

<code>
{{if packetery_is_pickup_point}} Pickup point: {{var packetery_point_id}} {{var packetery_point_name}} {{/if}}

{{if packetery_is_address_delivery}} Carrier name: {{var packetery_carrier_name}} {{/if}}
</code>

#### Restrictions:

- currently, the module does not support: delivery to non-EU addresses, carriers who have prohibited cash on delivery,
  evening delivery Prague, Brno, Ostrava, Bratislava

# Modul pro Magento 2

### Stažení modulu

[Stáhnout nejnovější verzi](https://github.com/Zasilkovna/magento2/releases/latest)

### Instalace

Instalace a registrace modulu se provádí CLI utilitou, která je součástí Magento 2.
Tato utilita je dostupná v instalačním adresáři Magenta jako "/bin/magento".

- nakopírovat adresář 'Packetery' do adresáře: `/app/code`
- povolení modulu pomocí CLI utility: `bin/magento module:enable Packetery_Checkout --clear-static-content`
- registrace modulu: `bin/magento setup:upgrade`
- re-deploy statického obsahu (není potřeba v dev módu): `bin/magento setup:static-content:deploy`
- rekompilace projektu: `bin/magento setup:di:compile`
- smazání cache: `bin/magento cache:clean`
- nastavte v administraci přepravce Zásilkovna pro výchozí kontext (scope), ikdyž je přepravce neaktivní
- nahrajte přepravce: `bin/magento packetery:import-feed-carriers`
- nastavte si v cronu volání příkazu `bin/magento cron:run` tak, aby se vám aktualizovali přepravci a spouštěli message queue consumeři.

### Aktualizace modulu

- nastavte v administraci přepravce Zásilkovna pro výchozí kontext (scope), ikdyž je přepravce neaktivní
- zapnout režim údržby: `bin/magento maintenance:enable`
- smazat zdrojové soubory (smazat složku app/code/Packetery)
- další postup stejný jako při instalaci
- (nepovinné) Migrace cenových pravidel z verzí 2.0.1 a 2.0.2: `bin/magento packetery:migrate-price-rules`
- (nepovinné) Migrace výchozí ceny do verze 2.0.5: `bin/magento packetery:migrate-default-price`
- vypnout režim údržby: `bin/magento maintenance:disable`
- zkontrolovat konfiguraci

#### Migrace cenových pravidel z verzí 2.0.1 a 2.0.2

V rámci této úlohy se migrují cenová pravidla v tomto rozsahu: pro země z původního seznamu pravidel
jsou pro variantu doručení na výdejní místo přenesena pravidla včetně hmotnostních rozsahů a ceny pro dopravu zdarma.

Dále je přenesena i maximální hmotnost a cena pro dopravu zdarma platné pro celý modul.

Cenová pravidla jsou vytvořena jako nedostupná.

Od verze 2.0.3 není nutné provádět.

#### Migrace výchozí ceny do verze 2.0.5

V rámci této úlohy se migruje výchozí cena pouze v případě, že v globálním nastavení modulu jsou zvoleny specifické země.

V případě, že jsou zvoleny všechny země, migrace se neprovádí. 

Cenová pravidla jsou vytvořena jako nedostupná, bez nastavené maximální hmotnosti.

### Vývoj

#### Kontrola kompatibility PHP

Modul obsahuje kontroly PHPCompatibility pro zajištění kompatibility s PHP 8.1 až PHP 8.5.

Pro spuštění kontrol je potřeba PHP 8.4 nebo novější a Composer 2.8.6 nebo novější. V rootu repozitáře (kde je top-level `composer.json`) spusťte:

```bash
composer install
composer phpcs-compatibility:81  # Kontrola kompatibility s PHP 8.1
composer phpcs-compatibility:84  # Kontrola kompatibility s PHP 8.4
composer phpcs-compatibility:85  # Kontrola kompatibility s PHP 8.5
```

Tyto příkazy používají PHP_CodeSniffer se standardem PHPCompatibility pro detekci problémů s kompatibilitou.

#### Marketplace (EQP) kontroly

Modul musí trvale splňovat blokující kontroly programu Magento Marketplace Extension Quality Program (EQP). Všechny kontroly níže se spouštějí lokálně z rootu repozitáře a vyžadují PHP 8.4 nebo novější a Composer 2.8.6 nebo novější, stejně jako kontroly kompatibility výše — kromě malware scanu, který místo toho potřebuje YARA a ClamAV; code sniffer a copy-paste detector používají stejné version constrainty a parametry jako CI (`.github/workflows/marketplace-checks.yml`).

Nejprve nainstalujte dev závislosti (pouze instaluje nástroje, žádné kontroly nespouští):

```bash
composer install
```

Tím se nainstalují i marketplace nástroje z `tools/composer.json` do `tools/vendor`.

**Code sniffer** (blokující; standard Magento2, pouze chyby severity 10):

```bash
composer phpcs-magento2
```

**Copy-paste detector** (informativní — nálezy je nutné ručně posoudit; duplicity vůči Magento core nebo jiným rozšířením jsou pro EQP blokující, nástroj má i false positives):

```bash
composer phpcpd
```

Skončí nenulovým kódem při jakémkoli nálezu, i neškodném — výstup slouží k posouzení, ne jako blokující brána.

**Copy-paste detector proti Magento core** (zatím informativní — CI job nález vypíše, ale run neshodí; tohle je duplicita, kterou EQP odmítá, kontrola výše porovnává modul jen sám se sebou):

```bash
composer phpcpd-core -- /cesta/k/magentu/vendor/magento
# když instalace po ruce není, stačí sparse clone se zdrojáky (~17 s, 112 MB)
git clone --depth 1 --branch 2.4.9 --filter=blob:none --sparse https://github.com/magento/magento2.git /tmp/magento-core
git -C /tmp/magento-core sparse-checkout set app/code/Magento lib/internal/Magento
composer phpcpd-core -- /tmp/magento-core/app/code/Magento /tmp/magento-core/lib/internal/Magento
```

⚠️ **Upozornění na výkon (Windows):** složku se staženým jádrem vylučte z testovaných složek v antiviru (zpravidla Windows Defenderu), jinak první běh nad čerstvým checkoutem může zabrat i desítky minut.

Hlásí jen klony přes hranici modulu, takže nález vždy znamená přebraný kód. Trvá asi 20 sekund proti clonu a 35 sekund proti `vendor/magento`; `memory_limit` si skript zvedá sám, špička běhu je kolem 1,5 GB.

**Package verification** (validní manifest, pravidla pro manifest rozšíření podle Adobe, shoda verze manifestu s verzí na začátku prvního řádku `CHANGE_LOG.txt`, zip balíčku do 30 MB, žádné TODO/FIXME mimo `/Test/`):

```bash
composer validate Packetery/Checkout/composer.json
composer verify-manifest
php -r 'echo json_decode(file_get_contents("Packetery/Checkout/composer.json"))->version, PHP_EOL;' && head -1 CHANGE_LOG.txt
(cd Packetery/Checkout && set -o pipefail && zip -rq - . -x '.git/*' | wc -c)   # bajty, limit 31457280
grep -rIniE '\b(TODO|FIXME)\b' --include='*.php' --include='*.phtml' --include='*.xml' --include='*.js' Packetery/Checkout | grep -v '/Test/' || echo 'OK: no TODO/FIXME'
```

`composer verify-manifest` přečte manifest a ověří pravidla z technical review guidelines Adobe: vyplněné `name`, `type` a `version`, povolený typ balíčku, žádné `extra.map` ani `extra.magento-root-dir`, žádná závislost na base balíčcích Magenta, žádná `*` u `magento/*`, žádné inline aliasy v require, `registration.php` v `autoload.files` a namespace v `autoload.psr-4`, a k tomu existující `registration.php` a parsovatelný `etc/module.xml`. Navíc porovná constraint `php` s maticí verzí v `.github/workflows/marketplace-checks.yml`, protože Adobe odmítá balíček, který zužuje rozsah PHP verzí daný podporovanými verzemi Magenta.

**PHP lint**:

```bash
find Packetery/Checkout -name '*.php' -not -path '*/vendor/*' -print0 | xargs -0 -n1 -P4 php -l >/dev/null
```

**Kompatibilita PHP** — viz sekce Kontrola kompatibility PHP výše.

**Malware scan** (pokrývá ho i CI, job `malware-scan`; finální scan provádí Adobe v rámci EQP, tohle je best-effort před-kontrola). Používají se dva nástroje po vzoru Adobe: antivirus ClamAV a YARA s komunitní sadou pravidel pro PHP malware/webshelly.

```bash
composer malware-scan
```

`bin/malware-scan` je totéž, co spouští CI job: stáhne sadu YARA pravidel na commitu zafixovaném v hlavičce skriptu, proskenuje s ní modul, pak spustí ClamAV a spadne jak na nálezu, tak na souboru, který se nepodařilo přečíst. Skript jen skenuje — oba nástroje musí být předem nainstalované a databáze signatur ClamAV aktuální, protože `freshclam` potřebuje roota a zůstává mimo skript.

Pro samotný ClamAV je nejrychlejší cesta Docker — funguje stejně na všech OS, nic se neinstaluje a image má aktuální databázi signatur:

```bash
docker run --rm -v "$PWD/Packetery/Checkout:/scan:ro" clamav/clamav:stable clamscan -r /scan
```

Na ARM strojích (Apple Silicon, ARM Linux) přidejte do příkazu `--platform linux/amd64` — image nemá ARM variantu a Docker bez toho skončí chybou o chybějícím manifestu.

Skript potřebuje oba nástroje nainstalované nativně:

- macOS: `brew install yara clamav`. ClamAV si pak vyžádá vytvoření konfigurace, jinak `freshclam` skončí chybou `Can't open/parse the config file`: `cp "$(brew --prefix)/etc/clamav/freshclam.conf.sample" "$(brew --prefix)/etc/clamav/freshclam.conf"` a v souboru zakomentujte řádek `Example`. Pokud se tomuhle nastavování chcete vyhnout, použijte Docker příkaz výše.
- Linux: `sudo apt install yara clamav` (Debian/Ubuntu), `sudo dnf install yara clamav clamav-update` (Fedora)
- Windows: lokální vývoj typicky běží ve WSL2 (Ubuntu) nebo v Docker kontejneru — použijte tam instrukce pro Linux. Nativní cesta je také možná: `choco install yara clamav`, nebo oficiální binárky (YARA: [github.com/VirusTotal/yara/releases](https://github.com/VirusTotal/yara/releases), ClamAV: [clamav.net/downloads](https://www.clamav.net/downloads))

ClamAV — aktualizace databáze signatur a scan adresáře modulu:

```bash
freshclam
clamscan -r Packetery/Checkout
```

YARA — s komunitní sadou pravidel [php-malware-finder](https://github.com/jvoisin/php-malware-finder):

```bash
git clone https://github.com/jvoisin/php-malware-finder /tmp/php-malware-finder
yara -w -r /tmp/php-malware-finder/data/php.yar Packetery/Checkout
```

Poznámky:

- Všechny příkazy v této sekci předpokládají bash nebo zsh (kontrola velikosti zipu používá `set -o pipefail`); na Windows je spouštějte ve WSL2 (nebo v Git Bash — kontrola velikosti zipu navíc potřebuje utilitu `zip`, kterou Git Bash neobsahuje; ve WSL2 ji doinstalujete přes `apt install zip`).
- Copy-paste detector proti Magento core potřebuje na Windows WSL2, Git Bash nestačí: nativní Windows PHP neumí otevřít cesty ve tvaru `/c/…`, které Git Bash vytváří, a shell si windowsový tvar cesty přepíše zpátky na něj. Skript se skenujícího PHP zeptá, jestli soubory vidí, takže to skončí hlasitou chybou, ne čistým během.
- Windows klony vytvořené dříve, než přibylo `bin/* text eol=lf`, si CRLF kopie ponechají — přepnutí větve nepřepíše soubor, jehož obsah se nezměnil — a `composer phpcs-compatibility:*` pak padá na `ERROR: The file "Packetery/Checkout" does not exist`. Jednorázově pomůže `rm bin/phpcs-compatibility && git checkout -- bin/phpcs-compatibility`.
- Balíčky na Debianu/Ubuntu s sebou nesou službu `clamav-freshclam`, která databázi signatur aktualizuje sama — ruční `freshclam` tam obvykle není potřeba. Pokud `freshclam` hlásí chybějící konfiguraci (typicky u Homebrew a Windows buildů), vytvořte `freshclam.conf` z přiloženého `freshclam.conf.sample` a zakomentujte řádek `Example`.
- Sadu YARA pravidel klonujte mimo repozitář — obsahuje adresář `samples/` s živými webshelly, které navíc mohou na firemním stroji spustit antivirus nebo ochranu koncových stanic. Když je nechcete stahovat vůbec, vezměte jen pravidla: `git clone --filter=blob:none --sparse https://github.com/jvoisin/php-malware-finder /tmp/php-malware-finder && git -C /tmp/php-malware-finder sparse-checkout set data` a potom smažte `data/samples`. `composer malware-scan` přesně tohle dělá sám, v dočasném adresáři.
- `--depth 1` do toho klonu nepatří: shallow klon obsahuje jen tip výchozí větve, takže `git checkout` zafixovaného commitu v něm skončí na `fatal: reference is not a tree`, jakmile se sada pravidel posune za pin.
- CI i lokální `composer malware-scan` skenují stejnými pravidly, protože pin je v `YARA_RULES_COMMIT` v hlavičce `bin/malware-scan` — změna pravidel je commit viditelný v pull requestu. ClamAV záměrně zafixovaný není a databázi si obnovuje při každém běhu, protože čerstvá databáze je u antiviru účel. Ruční `yara` nad čerstvým klonem tedy běží s novějšími pravidly než CI; pro reprodukci nálezu z CI si checkoutněte commit uvedený ve skriptu.
- `composer install` v rootu repozitáře doinstaluje i marketplace nástroje do `tools/`, takže potřebuje síť i tehdy, když chcete jen závislosti modulu. Za proxy nebo offline spadne až na tomto kroku, root `vendor/` už přitom stojí; pusťte ho znovu se sítí, nebo použijte `composer install --no-scripts` a nástroje doinstalujte později přes `composer install --working-dir=tools`.

#### Message queue consumer (hromadné podání zásilek)

Hromadné podání zásilek ze seznamu objednávek je zpracováno asynchronně přes Magento consumer:

- `packetery.checkout.packet.submit`

Pro spouštění consumerů přes Magento cron runner nastavte `app/etc/env.php`:

```php
'queue' => [
    'consumers_wait_for_messages' => 0
],
'cron_consumers_runner' => [
    'cron_run' => true,
    'max_messages' => 200
]
```

Pokud chcete přes cron runner spouštět pouze tento consumer, použijte:

```php
'cron_consumers_runner' => [
    'cron_run' => true,
    'max_messages' => 200,
    'consumers' => [
        'packetery.checkout.packet.submit'
    ]
]
```

Pokud se consumeři nespouští přes `bin/magento cron:run`, spusťte consumer pro podání zásilek ručně:

```bash
bin/magento queue:consumers:start packetery.checkout.packet.submit
```

#### Sledování stavu zásilky

U podaných zásilek se průběžně dotahuje aktuální stav z Packeta API a zobrazuje se v přehledu objednávek (sloupec `Packet status`). Cron vybere zásilky, které je potřeba aktualizovat (nejdéle nedotažené první), a předá je consumeru, který provede vlastní volání API:

- consumer: `packetery.checkout.packet.status.sync`
- cron job: `syncPacketStatus` (skupina `packetery`), bez commitnutého rozvrhu — spustí se až poté, co devops nastaví `cron_expr`

Consumer se spouští stejně jako consumer pro hromadné podání (přes `cron_consumers_runner` v `app/etc/env.php`, nebo ručně):

```bash
bin/magento queue:consumers:start packetery.checkout.packet.status.sync
```

Následující věci patří devops, nejde o volby pro eshopistu v administraci — ovlivňují zátěž serveru a fronty, proto žádná z nich není v admin UI. Klíč `status_polling/batch_size` je v poli `packetery/status_polling` v `app/etc/env.php`:

```php
'packetery' => [
    'status_polling' => [
        'batch_size' => 200   // vynechat nebo 0 = bez limitu
    ]
]
```

- **Strop počtu zásilek na běh** — `status_polling/batch_size`. Maximální počet zásilek, které cron zařadí za jeden běh; chybějící hodnota nebo `0` znamená bez limitu (zařadí všechny otevřené zásilky).
- **Zapnutí / vypnutí + frekvence** — cron config path `crontab/packetery/jobs/syncPacketStatus/schedule/cron_expr` (`packetery` = cron skupina jobu). Job nemá commitnutý výchozí rozvrh, takže tato hodnota je jediný vypínač: nastavením se funkce zapne a určí se, jak často běží, smazáním/nenastavením se vypne (hned po instalaci je funkce vypnutá). Nemá admin field, takže ho nastavte v `app/etc/config.php` ve stromu `system/default` (ne přes `bin/magento config:set`, který bere jen `system.xml` cesty):

```php
'system' => [
    'default' => [             // config scope
        'crontab' => [
            'packetery' => [   // cron skupina
                'jobs' => [
                    'syncPacketStatus' => [
                        'schedule' => ['cron_expr' => '17 */3 * * *']
                    ]
                ]
            ]
        ]
    ]
]
```

  Poté `bin/magento cache:flush config`.

#### Promazávání logu API akcí

API akce Zásilkovny (podání, storno, tisk štítku, tisk seznamu zásilek) provedené v administraci se
zaznamenávají do gridu **Packeta → Log**. Denní cron (`packetery_log_retention`, `Packetery\Checkout\Cron\LogPurger`)
maže záznamy starší než nastavený počet dní.

Doba uchování je provozní páka pro devops, ne nastavení v administraci, proto se nastavuje v deploy
konfiguraci (`app/etc/env.php` nebo `app/etc/config.php`):

```php
'packetery' => [
    'log' => [
        'retention_days' => 30
    ]
]
```

Chování: nenastaveno → výchozí `30`; kladné číslo `N` → denně se mažou záznamy starší než `N` dní;
`0` nebo záporná hodnota mazání vypne. Cron běží ve skupině `packetery` v `0 2 * * *`.

### Konfigurace a návod k použití

[Uživatelská dokumentace](https://github.com/Zasilkovna/magento2/wiki/U%C5%BEivatelsk%C3%A1-dokumentace)

### Informace o modulu

#### Podporované jazyky:

- čeština
- angličtina

#### Podporované verze:

- Magento 2.4.4+ (včetně 2.4.9)
- php 8.1 - 8.5
- Při problému s použitím modulu nás kontaktujte na emailu: [e-commerce.support@packeta.com](mailto:e-commerce.support@packeta.com)

#### Poskytované funkce:

- integrace widgetu v6 v košíku eshopu
- podpora výdejních míst externích dopravců
- doručení na adresu přes externí dopravce Zásilkovny
- nastavení různé ceny pro jednotlivé dopravce
- doprava zdarma od zadané ceny
- možnost hromadné úpravy hmotnosti v seznamu zásilek
- možnost hromadného podání zásilek ze seznamu objednávek (asynchronní zpracování přes message queue consumer)
- export zásilek do csv souboru, který lze importovat v [klientské sekci](https://client.packeta.com/)
- možnost změny výdejního místa u existující objednávky v administraci
- proměnné pro emailové šablony

#### Proměnné pro emailové šablony

<code>
{{if packetery_is_pickup_point}} Pickup point: {{var packetery_point_id}} {{var packetery_point_name}} {{/if}}

{{if packetery_is_address_delivery}} Carrier name: {{var packetery_carrier_name}} {{/if}}
</code>

#### Omezení:

- v současné době modul nepodporuje: doručení na adresu mimo EU, dopravce kteří mají zakázanou dobírku, večerní doručení Praha, Brno, Ostrava, Bratislava
