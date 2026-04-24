# Cloud Sync — New Entities Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend the existing cloud sync system to support Weapon, Ammo, Competition, CompetitionReminder, and AmmoTransaction entities on both the CakePHP backend and Flutter app.

**Architecture:** The backend gets 5 new `sync_*` tables + 3 new columns on `sync_sessions`, with corresponding Entity/Table classes. The `SyncService.php` typeConfig and processingOrder arrays are extended to include the new types, and `applyOwnershipFilter()` gains two new join strategies. The Flutter app gets a v32 migration adding sync fields to 4 tables + 3 UUID FK columns on Session, model updates with `toSyncMap()`, provider changes to use `syncAware*` methods, and SyncService push/pull extensions.

**Tech Stack:** CakePHP 5.3 (PHP 8.2+), Phinx migrations, PHPUnit; Flutter/Dart 3, sqflite, Provider

---

## File Structure

### Backend (triggertime-site)

**Create:**
- `config/Migrations/20260424000001_CreateSyncWeapons.php`
- `config/Migrations/20260424000002_CreateSyncAmmo.php`
- `config/Migrations/20260424000003_CreateSyncCompetitions.php`
- `config/Migrations/20260424000004_CreateSyncCompetitionReminders.php`
- `config/Migrations/20260424000005_CreateSyncAmmoTransactions.php`
- `config/Migrations/20260424000006_AddUuidFksToSyncSessions.php`
- `src/Model/Entity/SyncWeapon.php`
- `src/Model/Entity/SyncAmmo.php`
- `src/Model/Entity/SyncCompetition.php`
- `src/Model/Entity/SyncCompetitionReminder.php`
- `src/Model/Entity/SyncAmmoTransaction.php`
- `src/Model/Table/SyncWeaponsTable.php`
- `src/Model/Table/SyncAmmoTable.php`
- `src/Model/Table/SyncCompetitionsTable.php`
- `src/Model/Table/SyncCompetitionRemindersTable.php`
- `src/Model/Table/SyncAmmoTransactionsTable.php`
- `tests/Fixture/SyncWeaponsFixture.php`
- `tests/Fixture/SyncAmmoFixture.php`
- `tests/Fixture/SyncCompetitionsFixture.php`
- `tests/Fixture/SyncCompetitionRemindersFixture.php`
- `tests/Fixture/SyncAmmoTransactionsFixture.php`

**Modify:**
- `src/Service/SyncService.php` — extend typeConfig, processingOrder, applyOwnershipFilter, hasChanges
- `src/Model/Entity/SyncSession.php` — add weapon_uuid, ammo_uuid, competition_uuid to _accessible
- `tests/TestCase/Service/SyncServiceTest.php` — add tests for new entity types

### Flutter (triggertime)

**Modify:**
- `lib/services/database_service.dart` — add v32 migration
- `lib/models/weapon.dart` — add sync fields + toSyncMap()
- `lib/models/ammo.dart` — add sync fields + toSyncMap()
- `lib/models/competition.dart` — add toSyncMap()
- `lib/models/competition_reminder.dart` — add sync fields + toSyncMap()
- `lib/models/ammo_transaction.dart` — add sync fields + toSyncMap()
- `lib/models/session.dart` — add weapon_uuid, ammo_uuid, competition_uuid fields; update toSyncMap()
- `lib/providers/weapon_provider.dart` — switch to syncAware* + triggerSync()
- `lib/providers/ammo_provider.dart` — switch to syncAware* + triggerSync()
- `lib/providers/competition_provider.dart` — add triggerSync() calls; switch reminders to syncAware*
- `lib/services/sync_service.dart` — add push builders, pull appliers, ordering for new types
- `test/models/weapon_test.dart` — toSyncMap() test
- `test/models/ammo_test.dart` — toSyncMap() test
- `test/models/competition_test.dart` — toSyncMap() test
- `test/models/competition_reminder_test.dart` — toSyncMap() test
- `test/models/ammo_transaction_test.dart` — toSyncMap() test

---

## Task 1: Backend — Create sync_weapons migration + Entity + Table

**Files:**
- Create: `config/Migrations/20260424000001_CreateSyncWeapons.php`
- Create: `src/Model/Entity/SyncWeapon.php`
- Create: `src/Model/Table/SyncWeaponsTable.php`
- Create: `tests/Fixture/SyncWeaponsFixture.php`

- [ ] **Step 1: Create the migration file**

Create `config/Migrations/20260424000001_CreateSyncWeapons.php`:

```php
<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSyncWeapons extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('sync_weapons', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('device_uuid', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('caliber', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('serial_number', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('is_favorite', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('is_archived', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('shot_count', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('modified_at', 'datetime', ['null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['user_id'])
            ->addIndex(['user_id', 'modified_at'])
            ->addIndex(['user_id', 'deleted_at'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
```

- [ ] **Step 2: Create the Entity class**

Create `src/Model/Entity/SyncWeapon.php`:

```php
<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncWeapon extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'user_id' => true,
        'device_uuid' => true,
        'name' => true,
        'caliber' => true,
        'serial_number' => true,
        'notes' => true,
        'is_favorite' => true,
        'is_archived' => true,
        'shot_count' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
    ];
}
```

- [ ] **Step 3: Create the Table class**

Create `src/Model/Table/SyncWeaponsTable.php`:

```php
<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SyncWeaponsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sync_weapons');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->uuid('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('caliber')
            ->maxLength('caliber', 100)
            ->requirePresence('caliber', 'create')
            ->notEmptyString('caliber');

        $validator
            ->dateTime('modified_at')
            ->requirePresence('modified_at', 'create')
            ->notEmptyDateTime('modified_at');

        return $validator;
    }
}
```

- [ ] **Step 4: Create the test fixture**

Create `tests/Fixture/SyncWeaponsFixture.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SyncWeaponsFixture extends TestFixture
{
    public string $table = 'sync_weapons';

    public array $records = [];
}
```

- [ ] **Step 5: Run migration and verify**

Run: `bin/cake migrations migrate`
Expected: Migration runs successfully, `sync_weapons` table is created.

- [ ] **Step 6: Commit**

```bash
git add config/Migrations/20260424000001_CreateSyncWeapons.php src/Model/Entity/SyncWeapon.php src/Model/Table/SyncWeaponsTable.php tests/Fixture/SyncWeaponsFixture.php
git commit -m "feat(sync): add sync_weapons table, entity, and table class"
```

---

## Task 2: Backend — Create sync_ammo migration + Entity + Table

**Files:**
- Create: `config/Migrations/20260424000002_CreateSyncAmmo.php`
- Create: `src/Model/Entity/SyncAmmo.php`
- Create: `src/Model/Table/SyncAmmoTable.php`
- Create: `tests/Fixture/SyncAmmoFixture.php`

- [ ] **Step 1: Create the migration file**

Create `config/Migrations/20260424000002_CreateSyncAmmo.php`:

```php
<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSyncAmmo extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('sync_ammo', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('device_uuid', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('brand', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('caliber', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('grain_weight', 'integer', ['null' => true])
            ->addColumn('cost_per_round', 'decimal', ['precision' => 10, 'scale' => 4, 'null' => true])
            ->addColumn('current_stock', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('is_archived', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('modified_at', 'datetime', ['null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['user_id'])
            ->addIndex(['user_id', 'modified_at'])
            ->addIndex(['user_id', 'deleted_at'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
```

- [ ] **Step 2: Create the Entity class**

Create `src/Model/Entity/SyncAmmo.php`:

```php
<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncAmmo extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'user_id' => true,
        'device_uuid' => true,
        'brand' => true,
        'name' => true,
        'caliber' => true,
        'grain_weight' => true,
        'cost_per_round' => true,
        'current_stock' => true,
        'is_archived' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
        'sync_ammo_transactions' => true,
    ];
}
```

- [ ] **Step 3: Create the Table class**

Create `src/Model/Table/SyncAmmoTable.php`:

```php
<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SyncAmmoTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sync_ammo');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('SyncAmmoTransactions', [
            'foreignKey' => 'ammo_uuid',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->uuid('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('brand')
            ->maxLength('brand', 255)
            ->requirePresence('brand', 'create')
            ->notEmptyString('brand');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->dateTime('modified_at')
            ->requirePresence('modified_at', 'create')
            ->notEmptyDateTime('modified_at');

        return $validator;
    }
}
```

- [ ] **Step 4: Create the test fixture**

Create `tests/Fixture/SyncAmmoFixture.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SyncAmmoFixture extends TestFixture
{
    public string $table = 'sync_ammo';

    public array $records = [];
}
```

- [ ] **Step 5: Run migration and verify**

Run: `bin/cake migrations migrate`
Expected: Migration runs successfully, `sync_ammo` table is created.

- [ ] **Step 6: Commit**

```bash
git add config/Migrations/20260424000002_CreateSyncAmmo.php src/Model/Entity/SyncAmmo.php src/Model/Table/SyncAmmoTable.php tests/Fixture/SyncAmmoFixture.php
git commit -m "feat(sync): add sync_ammo table, entity, and table class"
```

---

## Task 3: Backend — Create sync_competitions migration + Entity + Table

**Files:**
- Create: `config/Migrations/20260424000003_CreateSyncCompetitions.php`
- Create: `src/Model/Entity/SyncCompetition.php`
- Create: `src/Model/Table/SyncCompetitionsTable.php`
- Create: `tests/Fixture/SyncCompetitionsFixture.php`

- [ ] **Step 1: Create the migration file**

Create `config/Migrations/20260424000003_CreateSyncCompetitions.php`:

```php
<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSyncCompetitions extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('sync_competitions', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('device_uuid', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('date', 'date', ['null' => false])
            ->addColumn('end_date', 'date', ['null' => true])
            ->addColumn('location', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('discipline_id', 'integer', ['null' => true])
            ->addColumn('status', 'string', ['limit' => 50, 'default' => 'interested', 'null' => false])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('is_active', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('modified_at', 'datetime', ['null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['user_id'])
            ->addIndex(['user_id', 'modified_at'])
            ->addIndex(['user_id', 'deleted_at'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
```

- [ ] **Step 2: Create the Entity class**

Create `src/Model/Entity/SyncCompetition.php`:

```php
<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncCompetition extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'user_id' => true,
        'device_uuid' => true,
        'name' => true,
        'date' => true,
        'end_date' => true,
        'location' => true,
        'discipline_id' => true,
        'status' => true,
        'notes' => true,
        'is_active' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
        'sync_competition_reminders' => true,
    ];
}
```

- [ ] **Step 3: Create the Table class**

Create `src/Model/Table/SyncCompetitionsTable.php`:

```php
<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SyncCompetitionsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sync_competitions');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('SyncCompetitionReminders', [
            'foreignKey' => 'competition_uuid',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->uuid('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->date('date')
            ->requirePresence('date', 'create')
            ->notEmptyDate('date');

        $validator
            ->dateTime('modified_at')
            ->requirePresence('modified_at', 'create')
            ->notEmptyDateTime('modified_at');

        return $validator;
    }
}
```

- [ ] **Step 4: Create the test fixture**

Create `tests/Fixture/SyncCompetitionsFixture.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SyncCompetitionsFixture extends TestFixture
{
    public string $table = 'sync_competitions';

    public array $records = [];
}
```

- [ ] **Step 5: Run migration and verify**

Run: `bin/cake migrations migrate`

- [ ] **Step 6: Commit**

```bash
git add config/Migrations/20260424000003_CreateSyncCompetitions.php src/Model/Entity/SyncCompetition.php src/Model/Table/SyncCompetitionsTable.php tests/Fixture/SyncCompetitionsFixture.php
git commit -m "feat(sync): add sync_competitions table, entity, and table class"
```

---

## Task 4: Backend — Create sync_competition_reminders migration + Entity + Table

**Files:**
- Create: `config/Migrations/20260424000004_CreateSyncCompetitionReminders.php`
- Create: `src/Model/Entity/SyncCompetitionReminder.php`
- Create: `src/Model/Table/SyncCompetitionRemindersTable.php`
- Create: `tests/Fixture/SyncCompetitionRemindersFixture.php`

- [ ] **Step 1: Create the migration file**

Create `config/Migrations/20260424000004_CreateSyncCompetitionReminders.php`:

```php
<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSyncCompetitionReminders extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('sync_competition_reminders', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('competition_uuid', 'uuid', ['null' => false])
            ->addColumn('reminder_offset', 'integer', ['null' => false])
            ->addColumn('is_enabled', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('modified_at', 'datetime', ['null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['competition_uuid'])
            ->addIndex(['competition_uuid', 'modified_at'])
            ->addForeignKey('competition_uuid', 'sync_competitions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
```

- [ ] **Step 2: Create the Entity class**

Create `src/Model/Entity/SyncCompetitionReminder.php`:

```php
<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncCompetitionReminder extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'competition_uuid' => true,
        'reminder_offset' => true,
        'is_enabled' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
    ];
}
```

- [ ] **Step 3: Create the Table class**

Create `src/Model/Table/SyncCompetitionRemindersTable.php`:

```php
<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SyncCompetitionRemindersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sync_competition_reminders');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('SyncCompetitions', [
            'foreignKey' => 'competition_uuid',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->uuid('competition_uuid')
            ->requirePresence('competition_uuid', 'create')
            ->notEmptyString('competition_uuid');

        $validator
            ->integer('reminder_offset')
            ->requirePresence('reminder_offset', 'create')
            ->notEmptyString('reminder_offset');

        $validator
            ->dateTime('modified_at')
            ->requirePresence('modified_at', 'create')
            ->notEmptyDateTime('modified_at');

        return $validator;
    }
}
```

- [ ] **Step 4: Create the test fixture**

Create `tests/Fixture/SyncCompetitionRemindersFixture.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SyncCompetitionRemindersFixture extends TestFixture
{
    public string $table = 'sync_competition_reminders';

    public array $records = [];
}
```

- [ ] **Step 5: Run migration and verify**

Run: `bin/cake migrations migrate`

- [ ] **Step 6: Commit**

```bash
git add config/Migrations/20260424000004_CreateSyncCompetitionReminders.php src/Model/Entity/SyncCompetitionReminder.php src/Model/Table/SyncCompetitionRemindersTable.php tests/Fixture/SyncCompetitionRemindersFixture.php
git commit -m "feat(sync): add sync_competition_reminders table, entity, and table class"
```

---

## Task 5: Backend — Create sync_ammo_transactions migration + Entity + Table

**Files:**
- Create: `config/Migrations/20260424000005_CreateSyncAmmoTransactions.php`
- Create: `src/Model/Entity/SyncAmmoTransaction.php`
- Create: `src/Model/Table/SyncAmmoTransactionsTable.php`
- Create: `tests/Fixture/SyncAmmoTransactionsFixture.php`

- [ ] **Step 1: Create the migration file**

Create `config/Migrations/20260424000005_CreateSyncAmmoTransactions.php`:

```php
<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSyncAmmoTransactions extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('sync_ammo_transactions', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['identity' => true])
            ->addColumn('ammo_uuid', 'uuid', ['null' => false])
            ->addColumn('type', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('quantity', 'integer', ['null' => false])
            ->addColumn('session_uuid', 'uuid', ['null' => true])
            ->addColumn('weapon_uuid', 'uuid', ['null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('modified_at', 'datetime', ['null' => false])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['ammo_uuid'])
            ->addIndex(['ammo_uuid', 'modified_at'])
            ->addForeignKey('ammo_uuid', 'sync_ammo', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
```

- [ ] **Step 2: Create the Entity class**

Create `src/Model/Entity/SyncAmmoTransaction.php`:

```php
<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncAmmoTransaction extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'ammo_uuid' => true,
        'type' => true,
        'quantity' => true,
        'session_uuid' => true,
        'weapon_uuid' => true,
        'notes' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
    ];
}
```

- [ ] **Step 3: Create the Table class**

Create `src/Model/Table/SyncAmmoTransactionsTable.php`:

```php
<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SyncAmmoTransactionsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sync_ammo_transactions');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('SyncAmmo', [
            'foreignKey' => 'ammo_uuid',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->uuid('ammo_uuid')
            ->requirePresence('ammo_uuid', 'create')
            ->notEmptyString('ammo_uuid');

        $validator
            ->scalar('type')
            ->maxLength('type', 50)
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->integer('quantity')
            ->requirePresence('quantity', 'create')
            ->notEmptyString('quantity');

        $validator
            ->dateTime('modified_at')
            ->requirePresence('modified_at', 'create')
            ->notEmptyDateTime('modified_at');

        return $validator;
    }
}
```

- [ ] **Step 4: Create the test fixture**

Create `tests/Fixture/SyncAmmoTransactionsFixture.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SyncAmmoTransactionsFixture extends TestFixture
{
    public string $table = 'sync_ammo_transactions';

    public array $records = [];
}
```

- [ ] **Step 5: Run migration and verify**

Run: `bin/cake migrations migrate`

- [ ] **Step 6: Commit**

```bash
git add config/Migrations/20260424000005_CreateSyncAmmoTransactions.php src/Model/Entity/SyncAmmoTransaction.php src/Model/Table/SyncAmmoTransactionsTable.php tests/Fixture/SyncAmmoTransactionsFixture.php
git commit -m "feat(sync): add sync_ammo_transactions table, entity, and table class"
```

---

## Task 6: Backend — Add UUID FK columns to sync_sessions + update Entity

**Files:**
- Create: `config/Migrations/20260424000006_AddUuidFksToSyncSessions.php`
- Modify: `src/Model/Entity/SyncSession.php`

- [ ] **Step 1: Create the migration file**

Create `config/Migrations/20260424000006_AddUuidFksToSyncSessions.php`:

```php
<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddUuidFksToSyncSessions extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('sync_sessions');
        $table->addColumn('weapon_uuid', 'uuid', ['null' => true, 'after' => 'auto_closed'])
            ->addColumn('ammo_uuid', 'uuid', ['null' => true, 'after' => 'weapon_uuid'])
            ->addColumn('competition_uuid', 'uuid', ['null' => true, 'after' => 'ammo_uuid'])
            ->update();
    }
}
```

- [ ] **Step 2: Update SyncSession Entity**

In `src/Model/Entity/SyncSession.php`, add `'weapon_uuid' => true`, `'ammo_uuid' => true`, and `'competition_uuid' => true` to the `$_accessible` array.

Read the current file first, then add the three fields before `'modified_at'`.

- [ ] **Step 3: Run migration and verify**

Run: `bin/cake migrations migrate`
Expected: 3 nullable UUID columns added to `sync_sessions`.

- [ ] **Step 4: Commit**

```bash
git add config/Migrations/20260424000006_AddUuidFksToSyncSessions.php src/Model/Entity/SyncSession.php
git commit -m "feat(sync): add weapon_uuid, ammo_uuid, competition_uuid to sync_sessions"
```

---

## Task 7: Backend — Update SyncService typeConfig, processingOrder, and ownership filters

**Files:**
- Modify: `src/Service/SyncService.php`

- [ ] **Step 1: Update typeConfig**

In `src/Service/SyncService.php`, replace the existing `$typeConfig` property (lines 16-47) with:

```php
private array $typeConfig = [
    'disciplines' => [
        'table' => 'SyncDisciplines',
        'ownership' => 'direct',
        'fkField' => 'user_id',
    ],
    'phases' => [
        'table' => 'SyncPhases',
        'ownership' => 'via_discipline',
        'fkField' => 'discipline_uuid',
    ],
    'weapons' => [
        'table' => 'SyncWeapons',
        'ownership' => 'direct',
        'fkField' => 'user_id',
    ],
    'ammo' => [
        'table' => 'SyncAmmo',
        'ownership' => 'direct',
        'fkField' => 'user_id',
    ],
    'competitions' => [
        'table' => 'SyncCompetitions',
        'ownership' => 'direct',
        'fkField' => 'user_id',
    ],
    'competition_reminders' => [
        'table' => 'SyncCompetitionReminders',
        'ownership' => 'via_competition',
        'fkField' => 'competition_uuid',
    ],
    'sessions' => [
        'table' => 'SyncSessions',
        'ownership' => 'direct',
        'fkField' => 'user_id',
    ],
    'series' => [
        'table' => 'SyncSeries',
        'ownership' => 'via_session',
        'fkField' => 'session_uuid',
    ],
    'shots' => [
        'table' => 'SyncShots',
        'ownership' => 'via_series',
        'fkField' => 'series_uuid',
    ],
    'strings' => [
        'table' => 'SyncStrings',
        'ownership' => 'via_session',
        'fkField' => 'session_uuid',
    ],
    'ammo_transactions' => [
        'table' => 'SyncAmmoTransactions',
        'ownership' => 'via_ammo',
        'fkField' => 'ammo_uuid',
    ],
];
```

- [ ] **Step 2: Update processingOrder**

Replace the existing `$processingOrder` property (lines 54-61) with:

```php
private array $processingOrder = [
    'disciplines',
    'phases',
    'weapons',
    'ammo',
    'competitions',
    'competition_reminders',
    'sessions',
    'series',
    'shots',
    'strings',
    'ammo_transactions',
];
```

- [ ] **Step 3: Add new ownership filter cases**

In the `applyOwnershipFilter()` method (around line 286), add two new cases inside the `switch ($config['ownership'])` block, after the existing `case 'via_series':` block:

```php
case 'via_competition':
    $query->innerJoinWith('SyncCompetitions', function ($q) use ($userId) {
        return $q->where(['SyncCompetitions.user_id' => $userId]);
    });
    break;

case 'via_ammo':
    $query->innerJoinWith('SyncAmmo', function ($q) use ($userId) {
        return $q->where(['SyncAmmo.user_id' => $userId]);
    });
    break;
```

- [ ] **Step 4: Update hasChanges() to check new direct-ownership tables**

The current `hasChanges()` only checks `SyncDisciplines` and `SyncSessions`. Add checks for `SyncWeapons`, `SyncAmmo`, and `SyncCompetitions`. Replace the method body with:

```php
public function hasChanges(string $userId, string $since): bool
{
    $sinceDate = new DateTime($since);

    $directTables = ['SyncDisciplines', 'SyncSessions', 'SyncWeapons', 'SyncAmmo', 'SyncCompetitions'];

    foreach ($directTables as $tableName) {
        $table = TableRegistry::getTableLocator()->get($tableName);
        $count = $table->find()
            ->where([
                'user_id' => $userId,
                'modified_at >' => $sinceDate,
            ])
            ->count();

        if ($count > 0) {
            return true;
        }
    }

    return false;
}
```

- [ ] **Step 5: Run existing tests to verify no regressions**

Run: `vendor/bin/phpunit tests/TestCase/Service/SyncServiceTest.php`
Expected: All existing tests pass.

- [ ] **Step 6: Commit**

```bash
git add src/Service/SyncService.php
git commit -m "feat(sync): extend SyncService with new entity types and ownership filters"
```

---

## Task 8: Backend — Add SyncService tests for new entity types

**Files:**
- Modify: `tests/TestCase/Service/SyncServiceTest.php`

- [ ] **Step 1: Update fixtures array**

Add the new fixture references to the `$fixtures` array in `SyncServiceTest`:

```php
protected array $fixtures = [
    'app.Users',
    'app.SyncDisciplines',
    'app.SyncPhases',
    'app.SyncSessions',
    'app.SyncSeries',
    'app.SyncShots',
    'app.SyncStrings',
    'app.SyncWeapons',
    'app.SyncAmmo',
    'app.SyncCompetitions',
    'app.SyncCompetitionReminders',
    'app.SyncAmmoTransactions',
];
```

- [ ] **Step 2: Add weapon push/pull tests**

Append to the test file:

```php
public function testPushInsertsNewWeapon(): void
{
    $uuid = 'w1w1w1w1-a2a2-4b3b-8c4c-d5d5d5d5d5d1';
    $records = [
        'weapons' => [
            [
                'uuid' => $uuid,
                'name' => 'CZ Shadow 2',
                'caliber' => '9mm',
                'serial_number' => 'SN12345',
                'notes' => null,
                'is_favorite' => true,
                'is_archived' => false,
                'shot_count' => 1500,
                'modified_at' => '2026-04-01T10:00:00+00:00',
            ],
        ],
    ];

    $result = $this->service->processPush($this->userId, $this->deviceUuid, $records);

    $this->assertContains($uuid, $result['accepted']);

    $table = TableRegistry::getTableLocator()->get('SyncWeapons');
    $entity = $table->get($uuid);
    $this->assertSame('CZ Shadow 2', $entity->name);
    $this->assertSame('9mm', $entity->caliber);
    $this->assertSame($this->userId, $entity->user_id);
    $this->assertSame(1500, $entity->shot_count);
}

public function testPullReturnsWeapons(): void
{
    $uuid = 'w2w2w2w2-b3b3-4c4c-8d5d-e6e6e6e6e6e2';
    $table = TableRegistry::getTableLocator()->get('SyncWeapons');
    $entity = $table->newEntity([
        'user_id' => $this->userId,
        'device_uuid' => $this->deviceUuid,
        'name' => 'Glock 17',
        'caliber' => '9mm',
        'is_favorite' => false,
        'is_archived' => false,
        'shot_count' => 0,
        'modified_at' => '2026-04-01 10:00:00',
    ], ['accessibleFields' => ['id' => true]]);
    $entity->id = $uuid;
    $table->saveOrFail($entity);

    $result = $this->service->processPull($this->userId, '2026-03-01T00:00:00+00:00');

    $this->assertArrayHasKey('weapons', $result['records']);
    $weaponUuids = array_column($result['records']['weapons'], 'uuid');
    $this->assertContains($uuid, $weaponUuids);
}
```

- [ ] **Step 3: Add competition + reminder push test**

```php
public function testPushInsertsCompetitionAndReminder(): void
{
    $compUuid = 'cp1cp1cp-a2a2-4b3b-8c4c-d5d5d5d5d5c1';
    $remUuid = 'cr1cr1cr-b3b3-4c4c-8d5d-e6e6e6e6e6r1';
    $records = [
        'competitions' => [
            [
                'uuid' => $compUuid,
                'name' => 'IPSC Level 2',
                'date' => '2026-06-15',
                'end_date' => '2026-06-16',
                'location' => 'Madrid',
                'discipline_id' => null,
                'status' => 'registered',
                'notes' => null,
                'is_active' => true,
                'modified_at' => '2026-04-01T10:00:00+00:00',
            ],
        ],
        'competition_reminders' => [
            [
                'uuid' => $remUuid,
                'competition_uuid' => $compUuid,
                'reminder_offset' => 1440,
                'is_enabled' => true,
                'modified_at' => '2026-04-01T10:00:00+00:00',
            ],
        ],
    ];

    $result = $this->service->processPush($this->userId, $this->deviceUuid, $records);

    $this->assertContains($compUuid, $result['accepted']);
    $this->assertContains($remUuid, $result['accepted']);

    $compTable = TableRegistry::getTableLocator()->get('SyncCompetitions');
    $comp = $compTable->get($compUuid);
    $this->assertSame('IPSC Level 2', $comp->name);
    $this->assertSame($this->userId, $comp->user_id);

    $remTable = TableRegistry::getTableLocator()->get('SyncCompetitionReminders');
    $rem = $remTable->get($remUuid);
    $this->assertSame($compUuid, $rem->competition_uuid);
    $this->assertSame(1440, $rem->reminder_offset);
}
```

- [ ] **Step 4: Add ammo + transaction push test**

```php
public function testPushInsertsAmmoAndTransaction(): void
{
    $ammoUuid = 'am1am1am-a2a2-4b3b-8c4c-d5d5d5d5d5a1';
    $txUuid = 'at1at1at-b3b3-4c4c-8d5d-e6e6e6e6e6t1';
    $records = [
        'ammo' => [
            [
                'uuid' => $ammoUuid,
                'brand' => 'Sellier & Bellot',
                'name' => 'FMJ',
                'caliber' => '9mm',
                'grain_weight' => 124,
                'cost_per_round' => 0.22,
                'current_stock' => 500,
                'is_archived' => false,
                'modified_at' => '2026-04-01T10:00:00+00:00',
            ],
        ],
        'ammo_transactions' => [
            [
                'uuid' => $txUuid,
                'ammo_uuid' => $ammoUuid,
                'type' => 'purchase',
                'quantity' => 500,
                'session_uuid' => null,
                'weapon_uuid' => null,
                'notes' => null,
                'modified_at' => '2026-04-01T10:00:00+00:00',
            ],
        ],
    ];

    $result = $this->service->processPush($this->userId, $this->deviceUuid, $records);

    $this->assertContains($ammoUuid, $result['accepted']);
    $this->assertContains($txUuid, $result['accepted']);

    $ammoTable = TableRegistry::getTableLocator()->get('SyncAmmo');
    $ammo = $ammoTable->get($ammoUuid);
    $this->assertSame('Sellier & Bellot', $ammo->brand);

    $txTable = TableRegistry::getTableLocator()->get('SyncAmmoTransactions');
    $tx = $txTable->get($txUuid);
    $this->assertSame($ammoUuid, $tx->ammo_uuid);
    $this->assertSame(500, $tx->quantity);
}
```

- [ ] **Step 5: Add session push with UUID FKs test**

```php
public function testPushSessionWithUuidFks(): void
{
    // First create weapon and competition
    $weaponUuid = 'w3w3w3w3-c4c4-4d5d-8e6e-f7f7f7f7f7w3';
    $compUuid = 'cp2cp2cp-d5d5-4e6e-8f7f-a8a8a8a8a8c2';
    $sessionUuid = 's1s1s1s1-e6e6-4f7f-8a8a-b9b9b9b9b9s1';

    $records = [
        'weapons' => [
            [
                'uuid' => $weaponUuid,
                'name' => 'Tanfoglio Stock 3',
                'caliber' => '9mm',
                'is_favorite' => false,
                'is_archived' => false,
                'shot_count' => 0,
                'modified_at' => '2026-04-01T10:00:00+00:00',
            ],
        ],
        'competitions' => [
            [
                'uuid' => $compUuid,
                'name' => 'Regional Match',
                'date' => '2026-06-20',
                'status' => 'registered',
                'is_active' => true,
                'modified_at' => '2026-04-01T10:00:00+00:00',
            ],
        ],
        'sessions' => [
            [
                'uuid' => $sessionUuid,
                'date' => '2026-06-20T09:00:00+00:00',
                'discipline_name' => 'IPSC Handgun',
                'type' => 'competition',
                'total_score' => 350,
                'total_x_count' => 12,
                'scoring_type_id' => 1,
                'auto_closed' => false,
                'weapon_uuid' => $weaponUuid,
                'ammo_uuid' => null,
                'competition_uuid' => $compUuid,
                'modified_at' => '2026-04-01T10:00:00+00:00',
            ],
        ],
    ];

    $result = $this->service->processPush($this->userId, $this->deviceUuid, $records);

    $this->assertContains($sessionUuid, $result['accepted']);

    $sessionTable = TableRegistry::getTableLocator()->get('SyncSessions');
    $session = $sessionTable->get($sessionUuid);
    $this->assertSame($weaponUuid, $session->weapon_uuid);
    $this->assertSame($compUuid, $session->competition_uuid);
    $this->assertNull($session->ammo_uuid);
}
```

- [ ] **Step 6: Add hasChanges test for weapons**

```php
public function testHasChangesDetectsWeaponChanges(): void
{
    $uuid = 'w4w4w4w4-d5d5-4e6e-8f7f-a8a8a8a8a8w4';
    $table = TableRegistry::getTableLocator()->get('SyncWeapons');
    $entity = $table->newEntity([
        'user_id' => $this->userId,
        'device_uuid' => $this->deviceUuid,
        'name' => 'Beretta 92',
        'caliber' => '9mm',
        'is_favorite' => false,
        'is_archived' => false,
        'shot_count' => 0,
        'modified_at' => '2026-04-01 10:00:00',
    ], ['accessibleFields' => ['id' => true]]);
    $entity->id = $uuid;
    $table->saveOrFail($entity);

    $result = $this->service->hasChanges($this->userId, '2026-03-01T00:00:00+00:00');
    $this->assertTrue($result);
}
```

- [ ] **Step 7: Run all tests**

Run: `vendor/bin/phpunit tests/TestCase/Service/SyncServiceTest.php`
Expected: All tests pass (existing + new).

- [ ] **Step 8: Commit**

```bash
git add tests/TestCase/Service/SyncServiceTest.php
git commit -m "test(sync): add tests for weapon, ammo, competition, and transaction sync"
```

---

## Task 9: Flutter — Database migration v32

**Files:**
- Modify: `lib/services/database_service.dart`

- [ ] **Step 1: Increment database version**

In `database_service.dart`, change the `version:` parameter in `openDatabase()` from `31` to `32`.

- [ ] **Step 2: Add _upgradeToV32 method**

Add the following method after the existing `_upgradeToV31` method (or wherever the latest upgrade method is):

```dart
Future<void> _upgradeToV32(Database db) async {
    // Weapon sync fields
    await db.execute('ALTER TABLE Weapon ADD COLUMN uuid TEXT UNIQUE');
    await db.execute(
        'ALTER TABLE Weapon ADD COLUMN sync_status INTEGER NOT NULL DEFAULT 0');
    await db.execute('ALTER TABLE Weapon ADD COLUMN modified_at TEXT');
    await db.execute('ALTER TABLE Weapon ADD COLUMN server_modified_at TEXT');

    // Ammo sync fields
    await db.execute('ALTER TABLE Ammo ADD COLUMN uuid TEXT UNIQUE');
    await db.execute(
        'ALTER TABLE Ammo ADD COLUMN sync_status INTEGER NOT NULL DEFAULT 0');
    await db.execute('ALTER TABLE Ammo ADD COLUMN modified_at TEXT');
    await db.execute('ALTER TABLE Ammo ADD COLUMN server_modified_at TEXT');

    // AmmoTransaction sync fields
    await db.execute(
        'ALTER TABLE AmmoTransaction ADD COLUMN uuid TEXT UNIQUE');
    await db.execute(
        'ALTER TABLE AmmoTransaction ADD COLUMN sync_status INTEGER NOT NULL DEFAULT 0');
    await db.execute(
        'ALTER TABLE AmmoTransaction ADD COLUMN modified_at TEXT');
    await db.execute(
        'ALTER TABLE AmmoTransaction ADD COLUMN server_modified_at TEXT');

    // CompetitionReminder sync fields
    await db.execute(
        'ALTER TABLE CompetitionReminder ADD COLUMN uuid TEXT UNIQUE');
    await db.execute(
        'ALTER TABLE CompetitionReminder ADD COLUMN sync_status INTEGER NOT NULL DEFAULT 0');
    await db.execute(
        'ALTER TABLE CompetitionReminder ADD COLUMN modified_at TEXT');
    await db.execute(
        'ALTER TABLE CompetitionReminder ADD COLUMN server_modified_at TEXT');

    // Session UUID FK columns for cross-device references
    await db.execute('ALTER TABLE Session ADD COLUMN weapon_uuid TEXT');
    await db.execute('ALTER TABLE Session ADD COLUMN ammo_uuid TEXT');
    await db.execute('ALTER TABLE Session ADD COLUMN competition_uuid TEXT');
}
```

- [ ] **Step 3: Wire migration into onUpgrade**

In the `onUpgrade` callback, add a case for version 32 that calls `_upgradeToV32(db)`. Follow the existing pattern used for v31.

- [ ] **Step 4: Update _onCreate to include new columns**

In `_onCreate`, update the `CREATE TABLE` statements for `Weapon`, `Ammo`, `AmmoTransaction`, `CompetitionReminder`, and `Session` to include the new columns so fresh installs get them.

For `Weapon`, add after the existing columns:
```sql
uuid TEXT UNIQUE,
sync_status INTEGER NOT NULL DEFAULT 0,
modified_at TEXT,
server_modified_at TEXT
```

For `Ammo`, add:
```sql
uuid TEXT UNIQUE,
sync_status INTEGER NOT NULL DEFAULT 0,
modified_at TEXT,
server_modified_at TEXT
```

For `AmmoTransaction`, add:
```sql
uuid TEXT UNIQUE,
sync_status INTEGER NOT NULL DEFAULT 0,
modified_at TEXT,
server_modified_at TEXT
```

For `CompetitionReminder`, add:
```sql
uuid TEXT UNIQUE,
sync_status INTEGER NOT NULL DEFAULT 0,
modified_at TEXT,
server_modified_at TEXT
```

For `Session`, add:
```sql
weapon_uuid TEXT,
ammo_uuid TEXT,
competition_uuid TEXT
```

- [ ] **Step 5: Run tests**

Run: `flutter test`
Expected: All existing tests pass.

- [ ] **Step 6: Commit**

```bash
git add lib/services/database_service.dart
git commit -m "feat(sync): add v32 migration for sync fields on weapon, ammo, transaction, reminder, session"
```

---

## Task 10: Flutter — Update models with sync fields and toSyncMap()

**Files:**
- Modify: `lib/models/weapon.dart`
- Modify: `lib/models/ammo.dart`
- Modify: `lib/models/competition.dart`
- Modify: `lib/models/competition_reminder.dart`
- Modify: `lib/models/ammo_transaction.dart`
- Modify: `lib/models/session.dart`

- [ ] **Step 1: Update Weapon model**

Add four new fields (`uuid`, `syncStatus`, `modifiedAt`, `serverModifiedAt`), update `toMap()`, `fromMap()`, and add `toSyncMap()`. The pattern matches `Competition` (which already has sync fields).

In `lib/models/weapon.dart`, add after existing fields:

```dart
final String? uuid;
final int syncStatus;
final String? modifiedAt;
final String? serverModifiedAt;
```

Add to constructor:
```dart
this.uuid,
this.syncStatus = 0,
this.modifiedAt,
this.serverModifiedAt,
```

Add to `toMap()`:
```dart
'uuid': uuid,
'sync_status': syncStatus,
'modified_at': modifiedAt,
'server_modified_at': serverModifiedAt,
```

Add to `fromMap()`:
```dart
uuid: map['uuid'] as String?,
syncStatus: (map['sync_status'] as int?) ?? 0,
modifiedAt: map['modified_at'] as String?,
serverModifiedAt: map['server_modified_at'] as String?,
```

Add new method:
```dart
Map<String, dynamic> toSyncMap() {
    return {
      'uuid': uuid,
      'name': name,
      'caliber': caliber,
      'serial_number': serialNumber,
      'notes': notes,
      'is_favorite': isFavorite,
      'is_archived': isArchived,
      'shot_count': shotCount,
      'modified_at': modifiedAt,
      'deleted': syncStatus == 2,
    };
  }
```

- [ ] **Step 2: Update Ammo model**

Same pattern as Weapon. In `lib/models/ammo.dart`, add:

Fields:
```dart
final String? uuid;
final int syncStatus;
final String? modifiedAt;
final String? serverModifiedAt;
```

Constructor params:
```dart
this.uuid,
this.syncStatus = 0,
this.modifiedAt,
this.serverModifiedAt,
```

`toMap()` additions:
```dart
'uuid': uuid,
'sync_status': syncStatus,
'modified_at': modifiedAt,
'server_modified_at': serverModifiedAt,
```

`fromMap()` additions:
```dart
uuid: map['uuid'] as String?,
syncStatus: (map['sync_status'] as int?) ?? 0,
modifiedAt: map['modified_at'] as String?,
serverModifiedAt: map['server_modified_at'] as String?,
```

New method:
```dart
Map<String, dynamic> toSyncMap() {
    return {
      'uuid': uuid,
      'brand': brand,
      'name': name,
      'caliber': caliber,
      'grain_weight': grainWeight,
      'cost_per_round': costPerRound,
      'current_stock': currentStock,
      'is_archived': isArchived,
      'modified_at': modifiedAt,
      'deleted': syncStatus == 2,
    };
  }
```

- [ ] **Step 3: Add toSyncMap() to Competition model**

Competition already has sync fields. Just add the `toSyncMap()` method to `lib/models/competition.dart`:

```dart
Map<String, dynamic> toSyncMap() {
    return {
      'uuid': uuid,
      'name': name,
      'date': date,
      'end_date': endDate,
      'location': location,
      'discipline_id': disciplineId,
      'status': status,
      'notes': notes,
      'is_active': isActive,
      'modified_at': modifiedAt,
      'deleted': syncStatus == 2,
    };
  }
```

- [ ] **Step 4: Update CompetitionReminder model**

In `lib/models/competition_reminder.dart`, add sync fields:

Fields:
```dart
final String? uuid;
final int syncStatus;
final String? modifiedAt;
final String? serverModifiedAt;
```

Constructor params:
```dart
this.uuid,
this.syncStatus = 0,
this.modifiedAt,
this.serverModifiedAt,
```

`toMap()` additions:
```dart
'uuid': uuid,
'sync_status': syncStatus,
'modified_at': modifiedAt,
'server_modified_at': serverModifiedAt,
```

`fromMap()` additions:
```dart
uuid: map['uuid'] as String?,
syncStatus: (map['sync_status'] as int?) ?? 0,
modifiedAt: map['modified_at'] as String?,
serverModifiedAt: map['server_modified_at'] as String?,
```

New method:
```dart
Map<String, dynamic> toSyncMap({required String competitionUuid}) {
    return {
      'uuid': uuid,
      'competition_uuid': competitionUuid,
      'reminder_offset': reminderOffset,
      'is_enabled': isEnabled,
      'modified_at': modifiedAt,
      'deleted': syncStatus == 2,
    };
  }
```

- [ ] **Step 5: Update AmmoTransaction model**

In `lib/models/ammo_transaction.dart`, add sync fields:

Fields:
```dart
final String? uuid;
final int syncStatus;
final String? modifiedAt;
final String? serverModifiedAt;
```

Constructor params:
```dart
this.uuid,
this.syncStatus = 0,
this.modifiedAt,
this.serverModifiedAt,
```

`toMap()` additions:
```dart
'uuid': uuid,
'sync_status': syncStatus,
'modified_at': modifiedAt,
'server_modified_at': serverModifiedAt,
```

`fromMap()` additions:
```dart
uuid: map['uuid'] as String?,
syncStatus: (map['sync_status'] as int?) ?? 0,
modifiedAt: map['modified_at'] as String?,
serverModifiedAt: map['server_modified_at'] as String?,
```

New method:
```dart
Map<String, dynamic> toSyncMap({
    required String ammoUuid,
    String? sessionUuid,
    String? weaponUuid,
  }) {
    return {
      'uuid': uuid,
      'ammo_uuid': ammoUuid,
      'type': type,
      'quantity': quantity,
      'session_uuid': sessionUuid,
      'weapon_uuid': weaponUuid,
      'notes': notes,
      'modified_at': modifiedAt,
      'deleted': syncStatus == 2,
    };
  }
```

- [ ] **Step 6: Update Session model**

In `lib/models/session.dart`, add three new fields:

Fields (after existing `competitionId`):
```dart
final String? weaponUuid;
final String? ammoUuid;
final String? competitionUuid;
```

Constructor params:
```dart
this.weaponUuid,
this.ammoUuid,
this.competitionUuid,
```

`toMap()` additions:
```dart
'weapon_uuid': weaponUuid,
'ammo_uuid': ammoUuid,
'competition_uuid': competitionUuid,
```

`fromMap()` additions:
```dart
weaponUuid: map['weapon_uuid'] as String?,
ammoUuid: map['ammo_uuid'] as String?,
competitionUuid: map['competition_uuid'] as String?,
```

Update `toSyncMap()` signature and body — add parameters and fields:
```dart
Map<String, dynamic> toSyncMap({
    String? disciplineUuid,
    required String disciplineName,
    String? weaponUuid,
    String? ammoUuid,
    String? competitionUuid,
  }) {
    return {
      'uuid': uuid,
      'date': date.toUtc().toIso8601String(),
      'end_date': endDate?.toUtc().toIso8601String(),
      'discipline_uuid': disciplineUuid,
      'discipline_name': disciplineName,
      'type': type,
      'location': location,
      'notes': notes,
      'total_score': totalScore,
      'total_x_count': totalXCount,
      'scoring_type_id': scoringTypeId,
      'auto_closed': autoClosed,
      'weapon_uuid': weaponUuid,
      'ammo_uuid': ammoUuid,
      'competition_uuid': competitionUuid,
      'modified_at': modifiedAt,
      'deleted': syncStatus == 2,
    };
  }
```

- [ ] **Step 7: Run tests**

Run: `flutter test`
Expected: All tests pass. If any existing test constructs models without the new fields, they should use defaults.

- [ ] **Step 8: Commit**

```bash
git add lib/models/weapon.dart lib/models/ammo.dart lib/models/competition.dart lib/models/competition_reminder.dart lib/models/ammo_transaction.dart lib/models/session.dart
git commit -m "feat(sync): add sync fields and toSyncMap() to weapon, ammo, competition, reminder, transaction, session models"
```

---

## Task 11: Flutter — Update WeaponProvider to use syncAware methods

**Files:**
- Modify: `lib/providers/weapon_provider.dart`

- [ ] **Step 1: Add SyncService import**

Add at the top of `lib/providers/weapon_provider.dart`:

```dart
import '../services/sync_service.dart';
```

- [ ] **Step 2: Replace addWeapon() with syncAware version**

Replace the body of `addWeapon()` (lines 35-57):

```dart
Future<void> addWeapon({
    required String name,
    required String caliber,
    String? serialNumber,
    String? notes,
    bool isFavorite = false,
    int shotCount = 0,
  }) async {
    final now = DateTime.now().toUtc().toIso8601String();
    final db = await _dbService.database;
    await DatabaseService.syncAwareInsert(db, 'Weapon', {
      'name': name,
      'caliber': caliber,
      'serial_number': serialNumber,
      'notes': notes,
      'is_favorite': isFavorite ? 1 : 0,
      'is_archived': 0,
      'shot_count': shotCount,
      'created_at': now,
      'updated_at': now,
    });
    await fetchWeapons();
    SyncService().triggerSync();
  }
```

- [ ] **Step 3: Replace updateWeapon() with syncAware version**

```dart
Future<void> updateWeapon(
    int id, {
    required String name,
    required String caliber,
    String? serialNumber,
    String? notes,
    bool? isFavorite,
    int? shotCount,
  }) async {
    final now = DateTime.now().toUtc().toIso8601String();
    final db = await _dbService.database;
    final values = <String, dynamic>{
      'name': name,
      'caliber': caliber,
      'serial_number': serialNumber,
      'notes': notes,
      'updated_at': now,
    };
    if (isFavorite != null) {
      values['is_favorite'] = isFavorite ? 1 : 0;
    }
    if (shotCount != null) {
      values['shot_count'] = shotCount;
    }
    await DatabaseService.syncAwareUpdate(
      db, 'Weapon', values,
      where: 'id = ?', whereArgs: [id],
    );
    await fetchWeapons();
    SyncService().triggerSync();
  }
```

- [ ] **Step 4: Replace archiveWeapon() with syncAware soft-delete**

```dart
Future<void> archiveWeapon(int id) async {
    final db = await _dbService.database;
    await DatabaseService.syncAwareSoftDelete(
      db, 'Weapon',
      where: 'id = ?', whereArgs: [id],
    );
    await fetchWeapons();
    SyncService().triggerSync();
  }
```

- [ ] **Step 5: Update restoreWeapon() with syncAware**

```dart
Future<void> restoreWeapon(int id) async {
    final now = DateTime.now().toUtc().toIso8601String();
    final db = await _dbService.database;
    await DatabaseService.syncAwareUpdate(
      db, 'Weapon', {'is_archived': 0, 'updated_at': now},
      where: 'id = ?', whereArgs: [id],
    );
    await fetchWeapons();
    SyncService().triggerSync();
  }
```

- [ ] **Step 6: Update fetchWeapons() to exclude soft-deleted**

Change the `where` clause in `fetchWeapons()` from `'is_archived = 0'` to `'is_archived = 0 AND sync_status != 2'`.

- [ ] **Step 7: Run tests**

Run: `flutter test`

- [ ] **Step 8: Commit**

```bash
git add lib/providers/weapon_provider.dart
git commit -m "feat(sync): update WeaponProvider to use syncAware methods"
```

---

## Task 12: Flutter — Update AmmoProvider to use syncAware methods

**Files:**
- Modify: `lib/providers/ammo_provider.dart`

- [ ] **Step 1: Add SyncService import**

```dart
import '../services/sync_service.dart';
```

- [ ] **Step 2: Replace addAmmo() with syncAware version**

```dart
Future<void> addAmmo({
    required String brand,
    required String name,
    required String caliber,
    int? grainWeight,
    double? costPerRound,
  }) async {
    final now = DateTime.now().toUtc().toIso8601String();
    final db = await _dbService.database;
    await DatabaseService.syncAwareInsert(db, 'Ammo', {
      'brand': brand,
      'name': name,
      'caliber': caliber,
      'grain_weight': grainWeight,
      'cost_per_round': costPerRound,
      'current_stock': 0,
      'is_archived': 0,
      'created_at': now,
      'updated_at': now,
    });
    await fetchAmmo();
    SyncService().triggerSync();
  }
```

- [ ] **Step 3: Replace updateAmmo() with syncAware version**

```dart
Future<void> updateAmmo(
    int id, {
    required String brand,
    required String name,
    required String caliber,
    int? grainWeight,
    double? costPerRound,
  }) async {
    final now = DateTime.now().toUtc().toIso8601String();
    final db = await _dbService.database;
    await DatabaseService.syncAwareUpdate(
      db,
      'Ammo',
      {
        'brand': brand,
        'name': name,
        'caliber': caliber,
        'grain_weight': grainWeight,
        'cost_per_round': costPerRound,
        'updated_at': now,
      },
      where: 'id = ?',
      whereArgs: [id],
    );
    await fetchAmmo();
    SyncService().triggerSync();
  }
```

- [ ] **Step 4: Replace addRounds() with syncAware version**

```dart
Future<void> addRounds(
    int ammoId, {
    required int quantity,
    double? totalPrice,
  }) async {
    final now = DateTime.now().toUtc().toIso8601String();
    final db = await _dbService.database;

    await DatabaseService.syncAwareInsert(db, 'AmmoTransaction', {
      'ammo_id': ammoId,
      'type': 'purchase',
      'quantity': quantity,
      'session_id': null,
      'notes': null,
      'created_at': now,
    });

    final updates = <String, dynamic>{
      'updated_at': now,
    };

    if (totalPrice != null && quantity > 0) {
      updates['cost_per_round'] = totalPrice / quantity;
    }

    final stockResult = await db.rawQuery(
      'SELECT COALESCE(SUM(quantity), 0) as total FROM AmmoTransaction WHERE ammo_id = ? AND sync_status != 2',
      [ammoId],
    );
    updates['current_stock'] = stockResult.first['total'] as int;

    await DatabaseService.syncAwareUpdate(
      db, 'Ammo', updates,
      where: 'id = ?', whereArgs: [ammoId],
    );
    await fetchAmmo();
    SyncService().triggerSync();
  }
```

- [ ] **Step 5: Replace deductRounds() with syncAware version**

```dart
Future<void> deductRounds(
    int ammoId, {
    required int quantity,
    required int sessionId,
  }) async {
    final now = DateTime.now().toUtc().toIso8601String();
    final db = await _dbService.database;

    await DatabaseService.syncAwareInsert(db, 'AmmoTransaction', {
      'ammo_id': ammoId,
      'type': 'session_usage',
      'quantity': -quantity,
      'session_id': sessionId,
      'notes': null,
      'created_at': now,
    });

    final stockResult = await db.rawQuery(
      'SELECT COALESCE(SUM(quantity), 0) as total FROM AmmoTransaction WHERE ammo_id = ? AND sync_status != 2',
      [ammoId],
    );

    await DatabaseService.syncAwareUpdate(
      db,
      'Ammo',
      {'current_stock': stockResult.first['total'] as int, 'updated_at': now},
      where: 'id = ?',
      whereArgs: [ammoId],
    );
    await fetchAmmo();
    SyncService().triggerSync();
  }
```

- [ ] **Step 6: Replace deductRoundsManual() with syncAware version**

```dart
Future<void> deductRoundsManual(
    int ammoId, {
    required int quantity,
    int? weaponId,
  }) async {
    final now = DateTime.now().toUtc().toIso8601String();
    final db = await _dbService.database;

    await DatabaseService.syncAwareInsert(db, 'AmmoTransaction', {
      'ammo_id': ammoId,
      'type': 'session_usage',
      'quantity': -quantity,
      'session_id': null,
      'weapon_id': weaponId,
      'notes': null,
      'created_at': now,
    });

    final stockResult = await db.rawQuery(
      'SELECT COALESCE(SUM(quantity), 0) as total FROM AmmoTransaction WHERE ammo_id = ? AND sync_status != 2',
      [ammoId],
    );

    await DatabaseService.syncAwareUpdate(
      db,
      'Ammo',
      {'current_stock': stockResult.first['total'] as int, 'updated_at': now},
      where: 'id = ?',
      whereArgs: [ammoId],
    );

    if (weaponId != null) {
      await DatabaseService.syncAwareUpdate(
        db,
        'Weapon',
        {'shot_count': (await db.rawQuery(
          'SELECT shot_count FROM Weapon WHERE id = ?',
          [weaponId],
        )).first['shot_count'] as int + quantity, 'updated_at': now},
        where: 'id = ?',
        whereArgs: [weaponId],
      );
    }

    await fetchAmmo();
    SyncService().triggerSync();
  }
```

- [ ] **Step 7: Replace archiveAmmo()/restoreAmmo() with syncAware versions**

```dart
Future<void> archiveAmmo(int id) async {
    final db = await _dbService.database;
    await DatabaseService.syncAwareSoftDelete(
      db, 'Ammo',
      where: 'id = ?', whereArgs: [id],
    );
    await fetchAmmo();
    SyncService().triggerSync();
  }

  Future<void> restoreAmmo(int id) async {
    final now = DateTime.now().toUtc().toIso8601String();
    final db = await _dbService.database;
    await DatabaseService.syncAwareUpdate(
      db, 'Ammo', {'is_archived': 0, 'updated_at': now},
      where: 'id = ?', whereArgs: [id],
    );
    await fetchAmmo();
    SyncService().triggerSync();
  }
```

- [ ] **Step 8: Update fetchAmmo() to exclude soft-deleted**

Change `where` from `'is_archived = 0'` to `'is_archived = 0 AND sync_status != 2'`.

- [ ] **Step 9: Run tests**

Run: `flutter test`

- [ ] **Step 10: Commit**

```bash
git add lib/providers/ammo_provider.dart
git commit -m "feat(sync): update AmmoProvider to use syncAware methods"
```

---

## Task 13: Flutter — Update CompetitionProvider to use syncAware for reminders + add triggerSync()

**Files:**
- Modify: `lib/providers/competition_provider.dart`

- [ ] **Step 1: Add SyncService import**

```dart
import '../services/sync_service.dart';
```

- [ ] **Step 2: Add triggerSync() calls to existing competition CRUD**

Competition already uses `syncAwareInsert`/`syncAwareUpdate`/`syncAwareSoftDelete`. Add `SyncService().triggerSync()` after `fetchCompetitions()` in:
- `addCompetition()` (after line 65)
- `updateCompetition()` (after line 94)
- `deleteCompetition()` (after line 124)

- [ ] **Step 3: Switch reminder operations to syncAware**

Replace `addReminder()`:

```dart
Future<void> addReminder(int competitionId, int offsetMinutes) async {
    final db = await _dbService.database;
    await DatabaseService.syncAwareInsert(db, 'CompetitionReminder', {
      'competition_id': competitionId,
      'reminder_offset': offsetMinutes,
      'is_enabled': 1,
    });
    SyncService().triggerSync();
  }
```

Replace `removeReminder()`:

```dart
Future<void> removeReminder(int reminderId) async {
    final db = await _dbService.database;
    await DatabaseService.syncAwareSoftDelete(
      db, 'CompetitionReminder',
      where: 'id = ?', whereArgs: [reminderId],
    );
    SyncService().triggerSync();
  }
```

Replace `toggleReminder()`:

```dart
Future<void> toggleReminder(int reminderId) async {
    final db = await _dbService.database;
    final maps = await db.query(
      'CompetitionReminder',
      where: 'id = ?',
      whereArgs: [reminderId],
    );
    if (maps.isEmpty) return;

    final current = maps.first['is_enabled'] as int;
    await DatabaseService.syncAwareUpdate(
      db,
      'CompetitionReminder',
      {'is_enabled': current == 1 ? 0 : 1},
      where: 'id = ?',
      whereArgs: [reminderId],
    );
    SyncService().triggerSync();
  }
```

- [ ] **Step 4: Update auto-created reminder in addCompetition()**

Replace the raw `db.insert('CompetitionReminder', ...)` in `addCompetition()` with:

```dart
await DatabaseService.syncAwareInsert(db, 'CompetitionReminder', {
    'competition_id': id,
    'reminder_offset': 1440,
    'is_enabled': 1,
});
```

- [ ] **Step 5: Update deleteCompetition() reminder deletion**

Replace the hard-delete of reminders with soft-delete:

```dart
// Soft-delete reminders
await DatabaseService.syncAwareSoftDelete(
    db,
    'CompetitionReminder',
    where: 'competition_id = ?',
    whereArgs: [id],
);
```

- [ ] **Step 6: Update fetchReminders() to exclude soft-deleted**

Change the query to filter `AND sync_status != 2`:

```dart
final maps = await db.query(
    'CompetitionReminder',
    where: 'competition_id = ? AND sync_status != 2',
    whereArgs: [competitionId],
    orderBy: 'reminder_offset ASC',
);
```

- [ ] **Step 7: Add triggerSync() to linkSession/unlinkSession**

Add `SyncService().triggerSync()` at the end of `linkSession()` and `unlinkSession()`.

- [ ] **Step 8: Run tests**

Run: `flutter test`

- [ ] **Step 9: Commit**

```bash
git add lib/providers/competition_provider.dart
git commit -m "feat(sync): update CompetitionProvider with triggerSync and syncAware reminders"
```

---

## Task 14: Flutter — Extend SyncService push with new entity types

**Files:**
- Modify: `lib/services/sync_service.dart`

- [ ] **Step 1: Add weapon push collection in _push()**

After the existing phases collection (around line 194), add:

```dart
// 3. Weapons
final dirtyWeapons = await db.query(
    'Weapon',
    where: 'sync_status > 0',
);
for (final row in dirtyWeapons) {
    records
        .putIfAbsent('weapons', () => [])
        .add(_buildWeaponSyncRecord(row));
}

// 4. Ammo
final dirtyAmmo = await db.query(
    'Ammo',
    where: 'sync_status > 0',
);
for (final row in dirtyAmmo) {
    records
        .putIfAbsent('ammo', () => [])
        .add(_buildAmmoSyncRecord(row));
}

// 5. Competitions
final dirtyCompetitions = await db.query(
    'Competition',
    where: 'sync_status > 0',
);
for (final row in dirtyCompetitions) {
    records
        .putIfAbsent('competitions', () => [])
        .add(_buildCompetitionSyncRecord(row));
}

// 6. Competition Reminders
final dirtyReminders = await db.rawQuery('''
    SELECT cr.* FROM CompetitionReminder cr
    INNER JOIN Competition c ON cr.competition_id = c.id
    WHERE cr.sync_status > 0
''');
for (final row in dirtyReminders) {
    records
        .putIfAbsent('competition_reminders', () => [])
        .add(await _buildCompetitionReminderSyncRecord(db, row));
}
```

- [ ] **Step 2: Add ammo transaction push collection**

After the existing strings collection (around line 238), add:

```dart
// 10. Ammo Transactions
final dirtyAmmoTx = await db.query(
    'AmmoTransaction',
    where: 'sync_status > 0',
);
for (final row in dirtyAmmoTx) {
    records
        .putIfAbsent('ammo_transactions', () => [])
        .add(await _buildAmmoTransactionSyncRecord(db, row));
}
```

- [ ] **Step 3: Update session push builder to include UUID FKs**

In `_buildSessionSyncRecord()`, add resolution of `weapon_uuid`, `ammo_uuid`, `competition_uuid`. After the existing `disciplineUuid` and `disciplineName` resolution:

```dart
final weaponUuid =
    await _resolveUuid(db, 'Weapon', row['weapon_id'] as int?);
final ammoUuid =
    await _resolveUuid(db, 'Ammo', row['ammo_id'] as int?);
final competitionUuid =
    await _resolveUuid(db, 'Competition', row['competition_id'] as int?);
```

Add to the returned map:
```dart
'weapon_uuid': weaponUuid,
'ammo_uuid': ammoUuid,
'competition_uuid': competitionUuid,
```

- [ ] **Step 4: Add push record builder methods**

```dart
Map<String, dynamic> _buildWeaponSyncRecord(Map<String, dynamic> row) {
    return {
      'uuid': row['uuid'],
      'deleted': row['sync_status'] == 2,
      'name': row['name'],
      'caliber': row['caliber'],
      'serial_number': row['serial_number'],
      'notes': row['notes'],
      'is_favorite': row['is_favorite'] == 1,
      'is_archived': row['is_archived'] == 1,
      'shot_count': row['shot_count'],
      'modified_at': row['modified_at'],
    };
  }

  Map<String, dynamic> _buildAmmoSyncRecord(Map<String, dynamic> row) {
    return {
      'uuid': row['uuid'],
      'deleted': row['sync_status'] == 2,
      'brand': row['brand'],
      'name': row['name'],
      'caliber': row['caliber'],
      'grain_weight': row['grain_weight'],
      'cost_per_round': row['cost_per_round'],
      'current_stock': row['current_stock'],
      'is_archived': row['is_archived'] == 1,
      'modified_at': row['modified_at'],
    };
  }

  Map<String, dynamic> _buildCompetitionSyncRecord(Map<String, dynamic> row) {
    return {
      'uuid': row['uuid'],
      'deleted': row['sync_status'] == 2,
      'name': row['name'],
      'date': row['date'],
      'end_date': row['end_date'],
      'location': row['location'],
      'discipline_id': row['discipline_id'],
      'status': row['status'],
      'notes': row['notes'],
      'is_active': row['is_active'] == 1,
      'modified_at': row['modified_at'],
    };
  }

  Future<Map<String, dynamic>> _buildCompetitionReminderSyncRecord(
      Database db, Map<String, dynamic> row) async {
    final competitionUuid = await _resolveUuid(
        db, 'Competition', row['competition_id'] as int?);
    return {
      'uuid': row['uuid'],
      'deleted': row['sync_status'] == 2,
      'competition_uuid': competitionUuid,
      'reminder_offset': row['reminder_offset'],
      'is_enabled': row['is_enabled'] == 1,
      'modified_at': row['modified_at'],
    };
  }

  Future<Map<String, dynamic>> _buildAmmoTransactionSyncRecord(
      Database db, Map<String, dynamic> row) async {
    final ammoUuid =
        await _resolveUuid(db, 'Ammo', row['ammo_id'] as int?);
    final sessionUuid =
        await _resolveUuid(db, 'Session', row['session_id'] as int?);
    final weaponUuid =
        await _resolveUuid(db, 'Weapon', row['weapon_id'] as int?);
    return {
      'uuid': row['uuid'],
      'deleted': row['sync_status'] == 2,
      'ammo_uuid': ammoUuid,
      'type': row['type'],
      'quantity': row['quantity'],
      'session_uuid': sessionUuid,
      'weapon_uuid': weaponUuid,
      'notes': row['notes'],
      'modified_at': row['modified_at'],
    };
  }
```

- [ ] **Step 5: Update markAccepted tables list**

In `_push()`, extend the table list in the `markAccepted` loop to include the new tables:

```dart
for (final table in [
    'Discipline',
    'Phase',
    'Weapon',
    'Ammo',
    'Competition',
    'CompetitionReminder',
    'Session',
    'Series',
    'Shot',
    'Strings',
    'AmmoTransaction',
]) {
    await markAccepted(db, table, acceptedSet);
}
```

- [ ] **Step 6: Run tests**

Run: `flutter test`

- [ ] **Step 7: Commit**

```bash
git add lib/services/sync_service.dart
git commit -m "feat(sync): extend SyncService push with weapon, ammo, competition, reminder, transaction"
```

---

## Task 15: Flutter — Extend SyncService pull with new entity types

**Files:**
- Modify: `lib/services/sync_service.dart`

- [ ] **Step 1: Update pluralToSingular mapping in _pull()**

Add new entries to the `pluralToSingular` map:

```dart
const pluralToSingular = {
    'disciplines': 'discipline',
    'phases': 'phase',
    'weapons': 'weapon',
    'ammo': 'ammo',
    'competitions': 'competition',
    'competition_reminders': 'competition_reminder',
    'sessions': 'session',
    'series': 'series',
    'shots': 'shot',
    'strings': 'string',
    'ammo_transactions': 'ammo_transaction',
};
```

- [ ] **Step 2: Update _orderByDependency()**

Replace the `typeOrder` map:

```dart
const typeOrder = {
    'discipline': 0,
    'phase': 1,
    'weapon': 2,
    'ammo': 3,
    'competition': 4,
    'competition_reminder': 5,
    'session': 6,
    'series': 7,
    'shot': 8,
    'string': 9,
    'ammo_transaction': 10,
};
```

- [ ] **Step 3: Update _applyRemoteRecord() switch**

Add new cases in `_applyRemoteRecord()`:

```dart
case 'weapon':
    await _applyWeapon(db, record);
case 'ammo':
    await _applyAmmo(db, record);
case 'competition':
    await _applyCompetition(db, record);
case 'competition_reminder':
    await _applyCompetitionReminder(db, record);
case 'ammo_transaction':
    await _applyAmmoTransaction(db, record);
```

- [ ] **Step 4: Add _applyWeapon() method**

```dart
Future<void> _applyWeapon(
      DatabaseExecutor db, Map<String, dynamic> record) async {
    final uuid = record['uuid'] as String;
    final deleted = record['deleted'] == true;
    final existing = await _findByUuid(db, 'Weapon', uuid);

    if (deleted) {
      if (existing != null) {
        await db.delete('Weapon', where: 'uuid = ?', whereArgs: [uuid]);
      }
      return;
    }

    final now = DateTime.now().toUtc().toIso8601String();
    final values = <String, dynamic>{
      'uuid': uuid,
      'name': record['name'],
      'caliber': record['caliber'],
      'serial_number': record['serial_number'],
      'notes': record['notes'],
      'is_favorite': _boolToInt(record['is_favorite']),
      'is_archived': _boolToInt(record['is_archived']),
      'shot_count': record['shot_count'] ?? 0,
      'sync_status': 0,
      'server_modified_at': record['modified_at'],
      'modified_at': record['modified_at'],
    };

    if (existing != null) {
      if (_isRemoteNewer(record['modified_at'] as String?, existing['modified_at'] as String?)) {
        await db.update('Weapon', values, where: 'uuid = ?', whereArgs: [uuid]);
      }
    } else {
      values['created_at'] = now;
      values['updated_at'] = now;
      await db.insert('Weapon', values);
    }
  }
```

- [ ] **Step 5: Add _applyAmmo() method**

```dart
Future<void> _applyAmmo(
      DatabaseExecutor db, Map<String, dynamic> record) async {
    final uuid = record['uuid'] as String;
    final deleted = record['deleted'] == true;
    final existing = await _findByUuid(db, 'Ammo', uuid);

    if (deleted) {
      if (existing != null) {
        await db.delete('Ammo', where: 'uuid = ?', whereArgs: [uuid]);
      }
      return;
    }

    final now = DateTime.now().toUtc().toIso8601String();
    final values = <String, dynamic>{
      'uuid': uuid,
      'brand': record['brand'],
      'name': record['name'],
      'caliber': record['caliber'],
      'grain_weight': record['grain_weight'],
      'cost_per_round': record['cost_per_round'],
      'current_stock': record['current_stock'] ?? 0,
      'is_archived': _boolToInt(record['is_archived']),
      'sync_status': 0,
      'server_modified_at': record['modified_at'],
      'modified_at': record['modified_at'],
    };

    if (existing != null) {
      if (_isRemoteNewer(record['modified_at'] as String?, existing['modified_at'] as String?)) {
        await db.update('Ammo', values, where: 'uuid = ?', whereArgs: [uuid]);
      }
    } else {
      values['created_at'] = now;
      values['updated_at'] = now;
      await db.insert('Ammo', values);
    }
  }
```

- [ ] **Step 6: Add _applyCompetition() method**

```dart
Future<void> _applyCompetition(
      DatabaseExecutor db, Map<String, dynamic> record) async {
    final uuid = record['uuid'] as String;
    final deleted = record['deleted'] == true;
    final existing = await _findByUuid(db, 'Competition', uuid);

    if (deleted) {
      if (existing != null) {
        await db.delete('Competition', where: 'uuid = ?', whereArgs: [uuid]);
      }
      return;
    }

    final values = <String, dynamic>{
      'uuid': uuid,
      'name': record['name'],
      'date': record['date'],
      'end_date': record['end_date'],
      'location': record['location'],
      'discipline_id': record['discipline_id'],
      'status': record['status'] ?? 'interested',
      'notes': record['notes'],
      'is_active': _boolToInt(record['is_active'] ?? true),
      'sync_status': 0,
      'server_modified_at': record['modified_at'],
      'modified_at': record['modified_at'],
    };

    if (existing != null) {
      if (_isRemoteNewer(record['modified_at'] as String?, existing['modified_at'] as String?)) {
        await db.update('Competition', values, where: 'uuid = ?', whereArgs: [uuid]);
      }
    } else {
      await db.insert('Competition', values);
    }
  }
```

- [ ] **Step 7: Add _applyCompetitionReminder() method**

```dart
Future<void> _applyCompetitionReminder(
      DatabaseExecutor db, Map<String, dynamic> record) async {
    final uuid = record['uuid'] as String;
    final deleted = record['deleted'] == true;
    final existing = await _findByUuid(db, 'CompetitionReminder', uuid);

    if (deleted) {
      if (existing != null) {
        await db.delete('CompetitionReminder', where: 'uuid = ?', whereArgs: [uuid]);
      }
      return;
    }

    final competitionId = await _resolveLocalId(
        db, 'Competition', record['competition_uuid'] as String?);
    if (competitionId == null) {
      _log('Pull: skipping reminder ${record['uuid']} — competition not found');
      return;
    }

    final values = <String, dynamic>{
      'uuid': uuid,
      'competition_id': competitionId,
      'reminder_offset': record['reminder_offset'],
      'is_enabled': _boolToInt(record['is_enabled']),
      'sync_status': 0,
      'server_modified_at': record['modified_at'],
      'modified_at': record['modified_at'],
    };

    if (existing != null) {
      if (_isRemoteNewer(record['modified_at'] as String?, existing['modified_at'] as String?)) {
        await db.update('CompetitionReminder', values, where: 'uuid = ?', whereArgs: [uuid]);
      }
    } else {
      await db.insert('CompetitionReminder', values);
    }
  }
```

- [ ] **Step 8: Add _applyAmmoTransaction() method**

```dart
Future<void> _applyAmmoTransaction(
      DatabaseExecutor db, Map<String, dynamic> record) async {
    final uuid = record['uuid'] as String;
    final deleted = record['deleted'] == true;
    final existing = await _findByUuid(db, 'AmmoTransaction', uuid);

    if (deleted) {
      if (existing != null) {
        await db.delete('AmmoTransaction', where: 'uuid = ?', whereArgs: [uuid]);
      }
      return;
    }

    final ammoId = await _resolveLocalId(
        db, 'Ammo', record['ammo_uuid'] as String?);
    if (ammoId == null) {
      _log('Pull: skipping ammo_transaction ${record['uuid']} — ammo not found');
      return;
    }
    final sessionId = await _resolveLocalId(
        db, 'Session', record['session_uuid'] as String?);
    final weaponId = await _resolveLocalId(
        db, 'Weapon', record['weapon_uuid'] as String?);

    final values = <String, dynamic>{
      'uuid': uuid,
      'ammo_id': ammoId,
      'type': record['type'],
      'quantity': record['quantity'],
      'session_id': sessionId,
      'weapon_id': weaponId,
      'notes': record['notes'],
      'sync_status': 0,
      'server_modified_at': record['modified_at'],
      'modified_at': record['modified_at'],
    };

    if (existing != null) {
      if (_isRemoteNewer(record['modified_at'] as String?, existing['modified_at'] as String?)) {
        await db.update('AmmoTransaction', values, where: 'uuid = ?', whereArgs: [uuid]);
      }
    } else {
      values['created_at'] = record['created'] ?? DateTime.now().toUtc().toIso8601String();
      await db.insert('AmmoTransaction', values);
    }
  }
```

- [ ] **Step 9: Update _applySession() to handle UUID FK columns**

In the existing `_applySession()`, add resolution for `weapon_uuid`, `ammo_uuid`, `competition_uuid` and include them in the values map:

After the existing `disciplineId` resolution:
```dart
final weaponId = await _resolveLocalId(
    db, 'Weapon', record['weapon_uuid'] as String?);
final ammoId = await _resolveLocalId(
    db, 'Ammo', record['ammo_uuid'] as String?);
final competitionId = await _resolveLocalId(
    db, 'Competition', record['competition_uuid'] as String?);
```

Add to the `values` map:
```dart
'weapon_id': weaponId,
'weapon_uuid': record['weapon_uuid'],
'ammo_id': ammoId,
'ammo_uuid': record['ammo_uuid'],
'competition_id': competitionId,
'competition_uuid': record['competition_uuid'],
```

- [ ] **Step 10: Run tests**

Run: `flutter test`

- [ ] **Step 11: Commit**

```bash
git add lib/services/sync_service.dart
git commit -m "feat(sync): extend SyncService pull with weapon, ammo, competition, reminder, transaction"
```

---

## Task 16: Run full test suites and verify

**Files:** None (validation only)

- [ ] **Step 1: Run backend tests**

Run from triggertime-site: `composer check`
Expected: All PHP tests, code sniffer pass.

- [ ] **Step 2: Run Flutter tests**

Run from triggertime: `flutter test`
Expected: All Dart tests pass.

- [ ] **Step 3: Run Flutter analysis**

Run: `flutter analyze`
Expected: No analysis issues.

- [ ] **Step 4: Run formatting check**

Run: `dart format --output=none --set-exit-if-changed .`
Expected: All files properly formatted.

- [ ] **Step 5: Commit any formatting fixes**

```bash
git commit -am "chore: format and fix any linting issues"
```
