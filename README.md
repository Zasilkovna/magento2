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

The module includes PHPCompatibility checks to ensure compatibility with PHP 8.1 and PHP 8.4.

Requirements to run the checks: PHP 8.4 and Composer 2.8.6. From the module directory (where `composer.json` lives), run:

```bash
composer install
composer phpcs-compatibility:81  # Check PHP 8.1 compatibility
composer phpcs-compatibility:84  # Check PHP 8.4 compatibility
```

These commands use PHP_CodeSniffer with the PHPCompatibility standard to detect compatibility issues.

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

- Magento 2.4.4+
- php 8.1 - 8.4
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

Modul obsahuje kontroly PHPCompatibility pro zajištění kompatibility s PHP 8.1 a PHP 8.4.

Pro spuštění kontrol je potřeba PHP 8.4 a Composer 2.8.6. V adresáři modulu (kde je `composer.json`) spusťte:

```bash
composer install
composer phpcs-compatibility:81  # Kontrola kompatibility s PHP 8.1
composer phpcs-compatibility:84  # Kontrola kompatibility s PHP 8.4
```

Tyto příkazy používají PHP_CodeSniffer se standardem PHPCompatibility pro detekci problémů s kompatibilitou.

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

- Magento 2.4.4+
- php 8.1 - 8.4
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
