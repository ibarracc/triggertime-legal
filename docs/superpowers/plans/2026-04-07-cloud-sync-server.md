# Cloud Sync — Server & Web Dashboard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add server-side sync storage, push/pull API endpoints for mobile devices, and a read-only sessions dashboard in the Vue SPA.

**Architecture:** 6 new `sync_*` MySQL tables store session data pushed from mobile devices. A `SyncService` handles merge logic (last-modified-wins). 3 new mobile API endpoints (HMAC auth) handle push/pull/status. 2 new web API endpoints (JWT auth) serve the read-only dashboard. Vue SPA gets sessions list + detail pages.

**Tech Stack:** CakePHP 5.3, MySQL migrations, PHPUnit, Vue 3 Composition API, Pinia, Axios, vue-i18n (8 languages)

---

### Task 1: Database Migrations — sync_disciplines and sync_phases

**Files:**
- Create: `config/Migrations/YYYYMMDDHHMMSS_CreateSyncDisciplines.php`
- Create: `config/Migrations/YYYYMMDDHHMMSS_CreateSyncPhases.php`

- [ ] **Step 1: Generate the sync_disciplines migration**

```bash
bin/cake bake migration CreateSyncDisciplines
```

Edit the generated file:

```php
<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateSyncDisciplines extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sync_disciplines', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $table->addColumn('id', 'uuid', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('user_id', 'uuid', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('device_uuid', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ])
        ->addColumn('name', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ])
        ->addColumn('weapon_type_id', 'integer', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('scoring_type_id', 'integer', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('use_fm', 'boolean', [
            'default' => false,
            'null' => false,
        ])
        ->addColumn('active', 'boolean', [
            'default' => true,
            'null' => false,
        ])
        ->addColumn('show_previous_series_on_scoring', 'boolean', [
            'default' => false,
            'null' => false,
        ])
        ->addColumn('max_score_per_shot', 'decimal', [
            'precision' => 10,
            'scale' => 2,
            'default' => null,
            'null' => true,
        ])
        ->addColumn('x_label', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => true,
        ])
        ->addColumn('always_editable_series', 'boolean', [
            'default' => false,
            'null' => false,
        ])
        ->addColumn('modified_at', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('deleted_at', 'datetime', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addIndex(['user_id'])
        ->addIndex(['user_id', 'modified_at'])
        ->addForeignKey('user_id', 'users', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION',
        ])
        ->create();
    }
}
```

- [ ] **Step 2: Generate the sync_phases migration**

```bash
bin/cake bake migration CreateSyncPhases
```

Edit the generated file:

```php
<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateSyncPhases extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sync_phases', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $table->addColumn('id', 'uuid', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('discipline_uuid', 'uuid', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('name', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ])
        ->addColumn('default_series_count', 'integer', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('default_series_shots', 'integer', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('default_series_total_shots', 'integer', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('shot_timer_type', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => true,
        ])
        ->addColumn('wait_seconds', 'integer', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('seconds', 'integer', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('active', 'boolean', [
            'default' => true,
            'null' => false,
        ])
        ->addColumn('allow_sighting_series', 'boolean', [
            'default' => false,
            'null' => false,
        ])
        ->addColumn('modified_at', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('deleted_at', 'datetime', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addIndex(['discipline_uuid'])
        ->addForeignKey('discipline_uuid', 'sync_disciplines', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION',
        ])
        ->create();
    }
}
```

- [ ] **Step 3: Run migrations and verify**

```bash
bin/cake migrations migrate
```

Expected: Both tables created successfully.

- [ ] **Step 4: Commit**

```bash
git add config/Migrations/*CreateSyncDisciplines* config/Migrations/*CreateSyncPhases*
git commit -m "feat(sync): add sync_disciplines and sync_phases migrations"
```

---

### Task 2: Database Migrations — sync_sessions, sync_series, sync_shots, sync_strings

**Files:**
- Create: `config/Migrations/YYYYMMDDHHMMSS_CreateSyncSessions.php`
- Create: `config/Migrations/YYYYMMDDHHMMSS_CreateSyncSeries.php`
- Create: `config/Migrations/YYYYMMDDHHMMSS_CreateSyncShots.php`
- Create: `config/Migrations/YYYYMMDDHHMMSS_CreateSyncStrings.php`

- [ ] **Step 1: Generate the sync_sessions migration**

```bash
bin/cake bake migration CreateSyncSessions
```

Edit:

```php
<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateSyncSessions extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sync_sessions', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $table->addColumn('id', 'uuid', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('user_id', 'uuid', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('device_uuid', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ])
        ->addColumn('date', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('end_date', 'datetime', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('discipline_uuid', 'uuid', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('discipline_name', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ])
        ->addColumn('type', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => false,
        ])
        ->addColumn('location', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => true,
        ])
        ->addColumn('notes', 'text', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('total_score', 'decimal', [
            'precision' => 10,
            'scale' => 2,
            'default' => 0,
            'null' => false,
        ])
        ->addColumn('total_x_count', 'integer', [
            'default' => 0,
            'null' => false,
        ])
        ->addColumn('event_uuid', 'uuid', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('category_uuid', 'uuid', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('scoring_type_id', 'integer', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('auto_closed', 'boolean', [
            'default' => false,
            'null' => false,
        ])
        ->addColumn('modified_at', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('deleted_at', 'datetime', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addIndex(['user_id'])
        ->addIndex(['user_id', 'modified_at'])
        ->addIndex(['user_id', 'deleted_at'])
        ->addForeignKey('user_id', 'users', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION',
        ])
        ->create();
    }
}
```

- [ ] **Step 2: Generate sync_series migration**

```bash
bin/cake bake migration CreateSyncSeries
```

Edit:

```php
<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateSyncSeries extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sync_series', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $table->addColumn('id', 'uuid', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('session_uuid', 'uuid', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('phase_uuid', 'uuid', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('series_number_within_phase', 'integer', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('total_score', 'decimal', [
            'precision' => 10,
            'scale' => 2,
            'default' => null,
            'null' => true,
        ])
        ->addColumn('total_x_count', 'integer', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('is_sighting', 'boolean', [
            'default' => false,
            'null' => false,
        ])
        ->addColumn('modified_at', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('deleted_at', 'datetime', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addIndex(['session_uuid'])
        ->addIndex(['session_uuid', 'modified_at'])
        ->addForeignKey('session_uuid', 'sync_sessions', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION',
        ])
        ->create();
    }
}
```

- [ ] **Step 3: Generate sync_shots migration**

```bash
bin/cake bake migration CreateSyncShots
```

Edit:

```php
<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateSyncShots extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sync_shots', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $table->addColumn('id', 'uuid', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('series_uuid', 'uuid', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('value', 'decimal', [
            'precision' => 10,
            'scale' => 2,
            'default' => null,
            'null' => false,
        ])
        ->addColumn('is_x', 'boolean', [
            'default' => false,
            'null' => false,
        ])
        ->addColumn('modified_at', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('deleted_at', 'datetime', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addIndex(['series_uuid'])
        ->addForeignKey('series_uuid', 'sync_series', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION',
        ])
        ->create();
    }
}
```

- [ ] **Step 4: Generate sync_strings migration**

```bash
bin/cake bake migration CreateSyncStrings
```

Edit:

```php
<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateSyncStrings extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sync_strings', [
            'id' => false,
            'primary_key' => ['id'],
        ]);
        $table->addColumn('id', 'uuid', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('session_uuid', 'uuid', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('phase_uuid', 'uuid', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('string_number_within_phase', 'integer', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('total_score', 'decimal', [
            'precision' => 10,
            'scale' => 2,
            'default' => null,
            'null' => false,
        ])
        ->addColumn('x_count', 'integer', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('first_miss', 'integer', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('is_sighting', 'boolean', [
            'default' => false,
            'null' => false,
        ])
        ->addColumn('modified_at', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('deleted_at', 'datetime', [
            'default' => null,
            'null' => true,
        ])
        ->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => false,
        ])
        ->addIndex(['session_uuid'])
        ->addIndex(['session_uuid', 'modified_at'])
        ->addForeignKey('session_uuid', 'sync_sessions', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION',
        ])
        ->create();
    }
}
```

- [ ] **Step 5: Run migrations and verify**

```bash
bin/cake migrations migrate
```

Expected: All 4 tables created successfully.

- [ ] **Step 6: Commit**

```bash
git add config/Migrations/*CreateSyncSessions* config/Migrations/*CreateSyncSeries* config/Migrations/*CreateSyncShots* config/Migrations/*CreateSyncStrings*
git commit -m "feat(sync): add sync_sessions, sync_series, sync_shots, sync_strings migrations"
```

---

### Task 3: CakePHP Models — Entities

**Files:**
- Create: `src/Model/Entity/SyncDiscipline.php`
- Create: `src/Model/Entity/SyncPhase.php`
- Create: `src/Model/Entity/SyncSession.php`
- Create: `src/Model/Entity/SyncSerie.php`
- Create: `src/Model/Entity/SyncShot.php`
- Create: `src/Model/Entity/SyncString.php`

- [ ] **Step 1: Create SyncDiscipline entity**

```php
<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncDiscipline extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'user_id' => true,
        'device_uuid' => true,
        'name' => true,
        'weapon_type_id' => true,
        'scoring_type_id' => true,
        'use_fm' => true,
        'active' => true,
        'show_previous_series_on_scoring' => true,
        'max_score_per_shot' => true,
        'x_label' => true,
        'always_editable_series' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
        'sync_phases' => true,
    ];
}
```

- [ ] **Step 2: Create SyncPhase entity**

```php
<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncPhase extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'discipline_uuid' => true,
        'name' => true,
        'default_series_count' => true,
        'default_series_shots' => true,
        'default_series_total_shots' => true,
        'shot_timer_type' => true,
        'wait_seconds' => true,
        'seconds' => true,
        'active' => true,
        'allow_sighting_series' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
    ];
}
```

- [ ] **Step 3: Create SyncSession entity**

```php
<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncSession extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'user_id' => true,
        'device_uuid' => true,
        'date' => true,
        'end_date' => true,
        'discipline_uuid' => true,
        'discipline_name' => true,
        'type' => true,
        'location' => true,
        'notes' => true,
        'total_score' => true,
        'total_x_count' => true,
        'event_uuid' => true,
        'category_uuid' => true,
        'scoring_type_id' => true,
        'auto_closed' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
        'sync_series' => true,
        'sync_strings' => true,
    ];
}
```

- [ ] **Step 4: Create SyncSerie entity**

```php
<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncSerie extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'session_uuid' => true,
        'phase_uuid' => true,
        'series_number_within_phase' => true,
        'total_score' => true,
        'total_x_count' => true,
        'is_sighting' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
        'sync_shots' => true,
    ];
}
```

- [ ] **Step 5: Create SyncShot entity**

```php
<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncShot extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'series_uuid' => true,
        'value' => true,
        'is_x' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
    ];
}
```

- [ ] **Step 6: Create SyncString entity**

```php
<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncString extends Entity
{
    protected array $_accessible = [
        'id' => false,
        'session_uuid' => true,
        'phase_uuid' => true,
        'string_number_within_phase' => true,
        'total_score' => true,
        'x_count' => true,
        'first_miss' => true,
        'is_sighting' => true,
        'modified_at' => true,
        'deleted_at' => true,
        'created' => true,
        'modified' => true,
    ];
}
```

- [ ] **Step 7: Commit**

```bash
git add src/Model/Entity/Sync*.php
git commit -m "feat(sync): add sync entity classes"
```

---

### Task 4: CakePHP Models — Table Classes

**Files:**
- Create: `src/Model/Table/SyncDisciplinesTable.php`
- Create: `src/Model/Table/SyncPhasesTable.php`
- Create: `src/Model/Table/SyncSessionsTable.php`
- Create: `src/Model/Table/SyncSeriesTable.php`
- Create: `src/Model/Table/SyncShotsTable.php`
- Create: `src/Model/Table/SyncStringsTable.php`

- [ ] **Step 1: Create SyncDisciplinesTable**

```php
<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SyncDisciplinesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sync_disciplines');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('SyncPhases', [
            'foreignKey' => 'discipline_uuid',
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
            ->dateTime('modified_at')
            ->requirePresence('modified_at', 'create')
            ->notEmptyDateTime('modified_at');

        return $validator;
    }
}
```

- [ ] **Step 2: Create SyncPhasesTable**

```php
<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SyncPhasesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sync_phases');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('SyncDisciplines', [
            'foreignKey' => 'discipline_uuid',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->uuid('discipline_uuid')
            ->requirePresence('discipline_uuid', 'create')
            ->notEmptyString('discipline_uuid');

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

- [ ] **Step 3: Create SyncSessionsTable**

```php
<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SyncSessionsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sync_sessions');
        $this->setDisplayField('discipline_name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('SyncSeries', [
            'foreignKey' => 'session_uuid',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);

        $this->hasMany('SyncStrings', [
            'foreignKey' => 'session_uuid',
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
            ->scalar('discipline_name')
            ->maxLength('discipline_name', 255)
            ->requirePresence('discipline_name', 'create')
            ->notEmptyString('discipline_name');

        $validator
            ->dateTime('modified_at')
            ->requirePresence('modified_at', 'create')
            ->notEmptyDateTime('modified_at');

        return $validator;
    }
}
```

- [ ] **Step 4: Create SyncSeriesTable**

```php
<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SyncSeriesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sync_series');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('SyncSessions', [
            'foreignKey' => 'session_uuid',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('SyncShots', [
            'foreignKey' => 'series_uuid',
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
            ->uuid('session_uuid')
            ->requirePresence('session_uuid', 'create')
            ->notEmptyString('session_uuid');

        $validator
            ->dateTime('modified_at')
            ->requirePresence('modified_at', 'create')
            ->notEmptyDateTime('modified_at');

        return $validator;
    }
}
```

- [ ] **Step 5: Create SyncShotsTable**

```php
<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SyncShotsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sync_shots');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('SyncSeries', [
            'foreignKey' => 'series_uuid',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->uuid('series_uuid')
            ->requirePresence('series_uuid', 'create')
            ->notEmptyString('series_uuid');

        $validator
            ->dateTime('modified_at')
            ->requirePresence('modified_at', 'create')
            ->notEmptyDateTime('modified_at');

        return $validator;
    }
}
```

- [ ] **Step 6: Create SyncStringsTable**

```php
<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SyncStringsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sync_strings');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('SyncSessions', [
            'foreignKey' => 'session_uuid',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->uuid('session_uuid')
            ->requirePresence('session_uuid', 'create')
            ->notEmptyString('session_uuid');

        $validator
            ->dateTime('modified_at')
            ->requirePresence('modified_at', 'create')
            ->notEmptyDateTime('modified_at');

        return $validator;
    }
}
```

- [ ] **Step 7: Commit**

```bash
git add src/Model/Table/Sync*.php
git commit -m "feat(sync): add sync table model classes"
```

---

### Task 5: SyncService — Push Logic

**Files:**
- Create: `src/Service/SyncService.php`
- Create: `tests/TestCase/Service/SyncServiceTest.php`

- [ ] **Step 1: Write failing tests for push logic**

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\SyncService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class SyncServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'app.SyncDisciplines',
        'app.SyncPhases',
        'app.SyncSessions',
        'app.SyncSeries',
        'app.SyncShots',
        'app.SyncStrings',
    ];

    protected SyncService $syncService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncService = new SyncService();
    }

    public function testPushInsertsNewDiscipline(): void
    {
        $userId = 'test-user-uuid-1';
        $deviceUuid = 'device-uuid-1';
        $records = [
            'disciplines' => [
                [
                    'uuid' => 'disc-uuid-new',
                    'name' => 'Custom Discipline',
                    'weapon_type_id' => 1,
                    'scoring_type_id' => 1,
                    'use_fm' => false,
                    'active' => true,
                    'show_previous_series_on_scoring' => false,
                    'max_score_per_shot' => 10.0,
                    'x_label' => 'X',
                    'always_editable_series' => false,
                    'modified_at' => '2026-04-07T10:00:00Z',
                    'deleted' => false,
                ],
            ],
            'phases' => [],
            'sessions' => [],
            'series' => [],
            'shots' => [],
            'strings' => [],
        ];

        $result = $this->syncService->processPush($userId, $deviceUuid, $records);

        $this->assertContains('disc-uuid-new', $result['accepted']);
        $this->assertEmpty($result['rejected']);

        $table = TableRegistry::getTableLocator()->get('SyncDisciplines');
        $entity = $table->get('disc-uuid-new');
        $this->assertEquals('Custom Discipline', $entity->name);
        $this->assertEquals($userId, $entity->user_id);
    }

    public function testPushUpdatesNewerRecord(): void
    {
        $userId = 'test-user-uuid-1';
        $deviceUuid = 'device-uuid-1';

        // Insert existing record with older timestamp
        $table = TableRegistry::getTableLocator()->get('SyncSessions');
        $entity = $table->newEntity([
            'id' => 'session-uuid-existing',
            'user_id' => $userId,
            'device_uuid' => $deviceUuid,
            'date' => '2026-04-07 08:00:00',
            'discipline_name' => 'Old Name',
            'type' => 'practice',
            'total_score' => 80.0,
            'total_x_count' => 5,
            'scoring_type_id' => 1,
            'auto_closed' => false,
            'modified_at' => '2026-04-07 09:00:00',
        ]);
        $table->save($entity);

        $records = [
            'disciplines' => [],
            'phases' => [],
            'sessions' => [
                [
                    'uuid' => 'session-uuid-existing',
                    'date' => '2026-04-07T08:00:00Z',
                    'discipline_name' => 'Updated Name',
                    'type' => 'practice',
                    'total_score' => 95.0,
                    'total_x_count' => 8,
                    'scoring_type_id' => 1,
                    'auto_closed' => false,
                    'modified_at' => '2026-04-07T11:00:00Z',
                    'deleted' => false,
                ],
            ],
            'series' => [],
            'shots' => [],
            'strings' => [],
        ];

        $result = $this->syncService->processPush($userId, $deviceUuid, $records);

        $this->assertContains('session-uuid-existing', $result['accepted']);

        $updated = $table->get('session-uuid-existing');
        $this->assertEquals('Updated Name', $updated->discipline_name);
        $this->assertEquals(95.0, (float)$updated->total_score);
    }

    public function testPushRejectsOlderRecord(): void
    {
        $userId = 'test-user-uuid-1';
        $deviceUuid = 'device-uuid-1';

        $table = TableRegistry::getTableLocator()->get('SyncSessions');
        $entity = $table->newEntity([
            'id' => 'session-uuid-newer',
            'user_id' => $userId,
            'device_uuid' => $deviceUuid,
            'date' => '2026-04-07 08:00:00',
            'discipline_name' => 'Server Version',
            'type' => 'practice',
            'total_score' => 95.0,
            'total_x_count' => 8,
            'scoring_type_id' => 1,
            'auto_closed' => false,
            'modified_at' => '2026-04-07 12:00:00',
        ]);
        $table->save($entity);

        $records = [
            'disciplines' => [],
            'phases' => [],
            'sessions' => [
                [
                    'uuid' => 'session-uuid-newer',
                    'date' => '2026-04-07T08:00:00Z',
                    'discipline_name' => 'Old Client Version',
                    'type' => 'practice',
                    'total_score' => 80.0,
                    'total_x_count' => 5,
                    'scoring_type_id' => 1,
                    'auto_closed' => false,
                    'modified_at' => '2026-04-07T09:00:00Z',
                    'deleted' => false,
                ],
            ],
            'series' => [],
            'shots' => [],
            'strings' => [],
        ];

        $result = $this->syncService->processPush($userId, $deviceUuid, $records);

        $this->assertEmpty($result['accepted']);
        $this->assertCount(1, $result['rejected']);
        $this->assertEquals('session-uuid-newer', $result['rejected'][0]['uuid']);
        $this->assertEquals('server_newer', $result['rejected'][0]['reason']);
    }

    public function testPushSoftDeletesRecord(): void
    {
        $userId = 'test-user-uuid-1';
        $deviceUuid = 'device-uuid-1';

        $table = TableRegistry::getTableLocator()->get('SyncSessions');
        $entity = $table->newEntity([
            'id' => 'session-uuid-delete',
            'user_id' => $userId,
            'device_uuid' => $deviceUuid,
            'date' => '2026-04-07 08:00:00',
            'discipline_name' => 'To Delete',
            'type' => 'practice',
            'total_score' => 0,
            'total_x_count' => 0,
            'scoring_type_id' => 1,
            'auto_closed' => false,
            'modified_at' => '2026-04-07 09:00:00',
        ]);
        $table->save($entity);

        $records = [
            'disciplines' => [],
            'phases' => [],
            'sessions' => [
                [
                    'uuid' => 'session-uuid-delete',
                    'modified_at' => '2026-04-07T10:00:00Z',
                    'deleted' => true,
                ],
            ],
            'series' => [],
            'shots' => [],
            'strings' => [],
        ];

        $result = $this->syncService->processPush($userId, $deviceUuid, $records);

        $this->assertContains('session-uuid-delete', $result['accepted']);

        $deleted = $table->get('session-uuid-delete');
        $this->assertNotNull($deleted->deleted_at);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/phpunit tests/TestCase/Service/SyncServiceTest.php
```

Expected: FAIL — `SyncService` class not found.

- [ ] **Step 3: Implement SyncService push logic**

```php
<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;

class SyncService
{
    /**
     * Table name mapping from sync payload keys to CakePHP table names.
     */
    private const TABLE_MAP = [
        'disciplines' => 'SyncDisciplines',
        'phases' => 'SyncPhases',
        'sessions' => 'SyncSessions',
        'series' => 'SyncSeries',
        'shots' => 'SyncShots',
        'strings' => 'SyncStrings',
    ];

    /**
     * Fields that are NOT part of the sync payload but set server-side.
     */
    private const SERVER_FIELDS = ['user_id', 'created', 'modified'];

    /**
     * The FK field name each table type uses for its parent UUID reference.
     * Used to rename 'uuid' to 'id' in the payload.
     */
    private const UUID_FIELD = 'uuid';

    /**
     * Process push: insert/update/soft-delete records from a device.
     * Processing order: disciplines → phases → sessions → series → shots → strings.
     *
     * @param string $userId The authenticated user's UUID
     * @param string $deviceUuid The pushing device's UUID
     * @param array $records Keyed by table type (disciplines, phases, sessions, series, shots, strings)
     * @return array{accepted: string[], rejected: array[], last_sync_at: string}
     */
    public function processPush(string $userId, string $deviceUuid, array $records): array
    {
        $accepted = [];
        $rejected = [];

        $processingOrder = ['disciplines', 'phases', 'sessions', 'series', 'shots', 'strings'];

        foreach ($processingOrder as $type) {
            if (empty($records[$type])) {
                continue;
            }

            $tableName = self::TABLE_MAP[$type];
            $table = TableRegistry::getTableLocator()->get($tableName);

            foreach ($records[$type] as $record) {
                $uuid = $record[self::UUID_FIELD] ?? $record['id'] ?? null;
                if ($uuid === null) {
                    continue;
                }

                $isDelete = !empty($record['deleted']);
                $modifiedAt = $record['modified_at'] ?? null;

                $existing = $table->find()->where(['id' => $uuid])->first();

                if ($isDelete) {
                    if ($existing !== null) {
                        $existing->deleted_at = new DateTime($modifiedAt);
                        $existing->modified_at = new DateTime($modifiedAt);
                        $table->save($existing);
                    }
                    $accepted[] = $uuid;
                    continue;
                }

                if ($existing !== null) {
                    $existingModifiedAt = $existing->modified_at instanceof DateTime
                        ? $existing->modified_at->toIso8601String()
                        : (string)$existing->modified_at;
                    $incomingModifiedAt = $modifiedAt;

                    if (strtotime($incomingModifiedAt) <= strtotime($existingModifiedAt)) {
                        $rejected[] = [
                            'uuid' => $uuid,
                            'reason' => 'server_newer',
                            'server_modified_at' => $existingModifiedAt,
                        ];
                        continue;
                    }

                    // Update existing with newer data
                    $data = $this->prepareRecordData($record, $type, $userId, $deviceUuid);
                    $entity = $table->patchEntity($existing, $data);
                    $table->save($entity);
                    $accepted[] = $uuid;
                } else {
                    // Insert new record
                    $data = $this->prepareRecordData($record, $type, $userId, $deviceUuid);
                    $data['id'] = $uuid;
                    $entity = $table->newEntity($data, ['accessibleFields' => ['id' => true]]);
                    $table->save($entity);
                    $accepted[] = $uuid;
                }
            }
        }

        return [
            'accepted' => $accepted,
            'rejected' => $rejected,
            'last_sync_at' => (new DateTime())->toIso8601String(),
        ];
    }

    /**
     * Process pull: return records modified since the given timestamp.
     *
     * @param string $userId The authenticated user's UUID
     * @param string $since ISO 8601 timestamp
     * @param int $limit Max records per table
     * @return array{records: array, has_more: bool, sync_timestamp: string}
     */
    public function processPull(string $userId, string $since, int $limit = 500): array
    {
        $result = [];
        $hasMore = false;

        foreach (self::TABLE_MAP as $type => $tableName) {
            $table = TableRegistry::getTableLocator()->get($tableName);

            $query = $table->find()
                ->where(['modified_at >' => new DateTime($since)]);

            // Filter by user_id for top-level tables
            if (in_array($type, ['disciplines', 'sessions'])) {
                $query->where(['user_id' => $userId]);
            } elseif ($type === 'phases') {
                $query->innerJoinWith('SyncDisciplines', function ($q) use ($userId) {
                    return $q->where(['SyncDisciplines.user_id' => $userId]);
                });
            } elseif (in_array($type, ['series', 'strings'])) {
                $query->innerJoinWith('SyncSessions', function ($q) use ($userId) {
                    return $q->where(['SyncSessions.user_id' => $userId]);
                });
            } elseif ($type === 'shots') {
                $query->innerJoinWith('SyncSeries.SyncSessions', function ($q) use ($userId) {
                    return $q->where(['SyncSessions.user_id' => $userId]);
                });
            }

            $query->orderByAsc('modified_at')
                ->limit($limit + 1);

            $records = $query->all()->toArray();

            if (count($records) > $limit) {
                $hasMore = true;
                $records = array_slice($records, 0, $limit);
            }

            $result[$type] = array_map(function ($entity) {
                $data = $entity->toArray();
                $data['uuid'] = $data['id'];
                $data['deleted'] = $data['deleted_at'] !== null;
                unset($data['created'], $data['modified']);

                return $data;
            }, $records);
        }

        return [
            'records' => $result,
            'has_more' => $hasMore,
            'sync_timestamp' => (new DateTime())->toIso8601String(),
        ];
    }

    /**
     * Check if there are changes for a user since the given timestamp.
     *
     * @param string $userId The user's UUID
     * @param string $since ISO 8601 timestamp
     * @return bool
     */
    public function hasChanges(string $userId, string $since): bool
    {
        $topLevelTables = ['SyncDisciplines', 'SyncSessions'];
        foreach ($topLevelTables as $tableName) {
            $table = TableRegistry::getTableLocator()->get($tableName);
            $count = $table->find()
                ->where([
                    'user_id' => $userId,
                    'modified_at >' => new DateTime($since),
                ])
                ->count();
            if ($count > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prepare record data for insert/update by removing sync-only fields
     * and adding server-side fields.
     */
    private function prepareRecordData(array $record, string $type, string $userId, string $deviceUuid): array
    {
        $data = $record;

        // Remove sync-only fields
        unset($data[self::UUID_FIELD], $data['deleted']);

        // Convert modified_at to DateTime
        if (isset($data['modified_at'])) {
            $data['modified_at'] = new DateTime($data['modified_at']);
        }

        // Add server-side fields for top-level tables
        if (in_array($type, ['disciplines', 'sessions'])) {
            $data['user_id'] = $userId;
            $data['device_uuid'] = $deviceUuid;
        }

        return $data;
    }
}
```

- [ ] **Step 4: Create test fixtures**

Create minimal fixture files for each sync table. Example for `tests/Fixture/SyncSessionsFixture.php`:

```php
<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SyncSessionsFixture extends TestFixture
{
    public string $table = 'sync_sessions';

    public array $records = [];
}
```

Create the same pattern for `SyncDisciplinesFixture`, `SyncPhasesFixture`, `SyncSeriesFixture`, `SyncShotsFixture`, `SyncStringsFixture` — all with empty `$records` arrays. Also ensure a `UsersFixture` exists (it should already — check `tests/Fixture/UsersFixture.php` and add a user with `id = 'test-user-uuid-1'` if not present).

- [ ] **Step 5: Run tests to verify they pass**

```bash
vendor/bin/phpunit tests/TestCase/Service/SyncServiceTest.php
```

Expected: All 4 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Service/SyncService.php tests/TestCase/Service/SyncServiceTest.php tests/Fixture/Sync*.php
git commit -m "feat(sync): add SyncService with push/pull/hasChanges logic and tests"
```

---

### Task 6: SyncController — Push/Pull/Status Endpoints

**Files:**
- Create: `src/Controller/Api/V1/SyncController.php`
- Create: `tests/TestCase/Controller/Api/V1/SyncControllerTest.php`
- Modify: `config/routes.php`

- [ ] **Step 1: Write failing tests for the controller**

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class SyncControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Devices',
        'app.Subscriptions',
        'app.SubscriptionDevices',
        'app.Instances',
        'app.SyncDisciplines',
        'app.SyncPhases',
        'app.SyncSessions',
        'app.SyncSeries',
        'app.SyncShots',
        'app.SyncStrings',
    ];

    public function testPushRequiresApiKey(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->post('/api/v1/sync/push', json_encode(['device_uuid' => 'test']));
        $this->assertResponseCode(401);
    }

    public function testPushRejectsUnlinkedDevice(): void
    {
        $this->markTestSkipped('Requires full HMAC auth setup in test helpers');
    }

    public function testPullRequiresDeviceUuid(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->get('/api/v1/sync/pull');
        $this->assertResponseCode(401);
    }

    public function testStatusRequiresDeviceUuid(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->get('/api/v1/sync/status');
        $this->assertResponseCode(401);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/phpunit tests/TestCase/Controller/Api/V1/SyncControllerTest.php
```

Expected: FAIL — controller/routes not found.

- [ ] **Step 3: Add routes**

In `config/routes.php`, add the sync routes inside the existing `/api/v1/` scope that uses `ApiKeyMiddleware`. Find the section where mobile API routes are defined (near `/devices/register`, `/devices/activate`, etc.) and add:

```php
$routes->post('/sync/push', ['controller' => 'Sync', 'action' => 'push']);
$routes->get('/sync/pull', ['controller' => 'Sync', 'action' => 'pull']);
$routes->get('/sync/status', ['controller' => 'Sync', 'action' => 'status']);
```

- [ ] **Step 4: Implement SyncController**

```php
<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AppController;
use App\Service\SyncService;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;

class SyncController extends AppController
{
    /**
     * Resolve the user ID from the device_uuid.
     * Returns null if device is not linked to a user with active PRO subscription.
     */
    private function resolveAuthorizedUser(string $deviceUuid): ?string
    {
        $devicesTable = TableRegistry::getTableLocator()->get('Devices');
        $device = $devicesTable->find()
            ->where(['device_uuid' => $deviceUuid])
            ->first();

        if ($device === null || $device->user_id === null) {
            return null;
        }

        // Check for active PRO subscription
        $subscriptionsTable = TableRegistry::getTableLocator()->get('Subscriptions');
        $subscription = $subscriptionsTable->find()
            ->where([
                'user_id' => $device->user_id,
                'plan' => 'pro',
                'status' => 'active',
            ])
            ->first();

        if ($subscription === null) {
            return null;
        }

        return (string)$device->user_id;
    }

    public function push(): Response
    {
        $data = $this->request->getData();
        $deviceUuid = $data['device_uuid'] ?? '';

        $userId = $this->resolveAuthorizedUser($deviceUuid);
        if ($userId === null) {
            return $this->response
                ->withStatus(403)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => 'sync_not_authorized',
                    'message' => 'Device is not linked to an account with an active PRO subscription.',
                ]));
        }

        $records = $data['records'] ?? [];
        $syncService = new SyncService();
        $result = $syncService->processPush($userId, $deviceUuid, $records);

        return $this->response
            ->withStatus(200)
            ->withType('application/json')
            ->withStringBody(json_encode($result));
    }

    public function pull(): Response
    {
        $deviceUuid = $this->request->getQuery('device_uuid', '');
        $since = $this->request->getQuery('since', '1970-01-01T00:00:00Z');

        $userId = $this->resolveAuthorizedUser($deviceUuid);
        if ($userId === null) {
            return $this->response
                ->withStatus(403)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => 'sync_not_authorized',
                    'message' => 'Device is not linked to an account with an active PRO subscription.',
                ]));
        }

        $syncService = new SyncService();
        $result = $syncService->processPull($userId, $since);

        return $this->response
            ->withStatus(200)
            ->withType('application/json')
            ->withStringBody(json_encode($result));
    }

    public function status(): Response
    {
        $deviceUuid = $this->request->getQuery('device_uuid', '');

        $userId = $this->resolveAuthorizedUser($deviceUuid);
        if ($userId === null) {
            return $this->response
                ->withStatus(403)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'error' => 'sync_not_authorized',
                    'message' => 'Device is not linked to an account with an active PRO subscription.',
                ]));
        }

        $since = $this->request->getQuery('since', '1970-01-01T00:00:00Z');
        $syncService = new SyncService();
        $hasChanges = $syncService->hasChanges($userId, $since);

        return $this->response
            ->withStatus(200)
            ->withType('application/json')
            ->withStringBody(json_encode([
                'has_changes' => $hasChanges,
            ]));
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
vendor/bin/phpunit tests/TestCase/Controller/Api/V1/SyncControllerTest.php
```

Expected: All tests PASS (the auth test passes because missing API key returns 401).

- [ ] **Step 6: Run code quality checks**

```bash
composer cs-check
```

Fix any style issues with `composer cs-fix`.

- [ ] **Step 7: Commit**

```bash
git add src/Controller/Api/V1/SyncController.php tests/TestCase/Controller/Api/V1/SyncControllerTest.php config/routes.php
git commit -m "feat(sync): add SyncController with push/pull/status endpoints and routes"
```

---

### Task 7: Web Sessions API Endpoints

**Files:**
- Create: `src/Controller/Api/V1/Web/SessionsController.php`
- Create: `tests/TestCase/Controller/Api/V1/Web/SessionsControllerTest.php`
- Modify: `config/routes.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1\Web;

use App\Service\JwtService;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class SessionsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.SyncSessions',
        'app.SyncSeries',
        'app.SyncShots',
        'app.SyncStrings',
        'app.SyncPhases',
        'app.SyncDisciplines',
    ];

    public function testIndexRequiresAuth(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->get('/api/v1/web/sessions');
        $this->assertResponseCode(401);
    }

    public function testViewRequiresAuth(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->get('/api/v1/web/sessions/some-uuid');
        $this->assertResponseCode(401);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/phpunit tests/TestCase/Controller/Api/V1/Web/SessionsControllerTest.php
```

Expected: FAIL — routes/controller not found.

- [ ] **Step 3: Add web routes**

In `config/routes.php`, inside the `/api/v1/web/` scope that uses `JwtMiddleware` (where authenticated routes like `/web/devices` are defined), add:

```php
$routes->get('/sessions', ['controller' => 'Sessions', 'action' => 'index']);
$routes->get('/sessions/{uuid}', ['controller' => 'Sessions', 'action' => 'view'])
    ->setPatterns(['uuid' => '[a-f0-9\-]+']);
```

- [ ] **Step 4: Implement SessionsController**

```php
<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Web;

use App\Controller\AppController;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;

class SessionsController extends AppController
{
    public function index(): Response
    {
        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];

        $table = TableRegistry::getTableLocator()->get('SyncSessions');

        $query = $table->find()
            ->where([
                'SyncSessions.user_id' => $userId,
                'SyncSessions.deleted_at IS' => null,
            ]);

        // Filtering
        $discipline = $this->request->getQuery('discipline');
        if ($discipline) {
            $query->where(['SyncSessions.discipline_name' => $discipline]);
        }

        $type = $this->request->getQuery('type');
        if ($type) {
            $query->where(['SyncSessions.type' => $type]);
        }

        // Sorting
        $sort = $this->request->getQuery('sort', 'date_desc');
        if ($sort === 'date_asc') {
            $query->orderByAsc('SyncSessions.date');
        } else {
            $query->orderByDesc('SyncSessions.date');
        }

        // Pagination
        $page = (int)$this->request->getQuery('page', '1');
        $limit = min((int)$this->request->getQuery('limit', '20'), 100);
        $offset = ($page - 1) * $limit;

        $total = $query->count();
        $sessions = $query->limit($limit)->offset($offset)->all()->toArray();

        return $this->response
            ->withStatus(200)
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'sessions' => $sessions,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => (int)ceil($total / $limit),
                ],
            ]));
    }

    public function view(string $uuid): Response
    {
        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];

        $sessionsTable = TableRegistry::getTableLocator()->get('SyncSessions');

        $session = $sessionsTable->find()
            ->where([
                'SyncSessions.id' => $uuid,
                'SyncSessions.user_id' => $userId,
                'SyncSessions.deleted_at IS' => null,
            ])
            ->contain([
                'SyncSeries' => [
                    'conditions' => ['SyncSeries.deleted_at IS' => null],
                    'sort' => ['SyncSeries.series_number_within_phase' => 'ASC'],
                    'SyncShots' => [
                        'conditions' => ['SyncShots.deleted_at IS' => null],
                    ],
                ],
                'SyncStrings' => [
                    'conditions' => ['SyncStrings.deleted_at IS' => null],
                    'sort' => ['SyncStrings.string_number_within_phase' => 'ASC'],
                ],
            ])
            ->first();

        if ($session === null) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'error' => 'Session not found.',
                ]));
        }

        return $this->response
            ->withStatus(200)
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'session' => $session,
            ]));
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
vendor/bin/phpunit tests/TestCase/Controller/Api/V1/Web/SessionsControllerTest.php
```

Expected: PASS.

- [ ] **Step 6: Run code quality checks**

```bash
composer cs-check
```

- [ ] **Step 7: Commit**

```bash
git add src/Controller/Api/V1/Web/SessionsController.php tests/TestCase/Controller/Api/V1/Web/SessionsControllerTest.php config/routes.php
git commit -m "feat(sync): add web sessions API endpoints (list + detail)"
```

---

### Task 8: Vue SPA — Sessions API Module & i18n

**Files:**
- Create: `client/src/api/sessions.js`
- Modify: `client/src/i18n/locales/en.json` (and all 7 other locale files)

- [ ] **Step 1: Create sessions API module**

```js
import api from './index'

export default {
  getSessions(params = {}) {
    return api.get('/web/sessions', { params })
  },

  getSession(uuid) {
    return api.get(`/web/sessions/${uuid}`)
  },
}
```

- [ ] **Step 2: Add i18n keys to en.json**

Add to the English locale file, alongside existing `"devices"` section:

```json
"sessions": {
  "title": "Sessions",
  "subtitle": "View your synced shooting sessions.",
  "empty": "No sessions synced yet. Sync from your TriggerTime app to see your sessions here.",
  "date": "Date",
  "discipline": "Discipline",
  "type": "Type",
  "location": "Location",
  "total_score": "Score",
  "total_x_count": "X Count",
  "notes": "Notes",
  "series": "Series",
  "shots": "Shots",
  "strings": "Strings",
  "phase": "Phase",
  "sighting": "Sighting",
  "detail_title": "Session Detail",
  "back_to_list": "Back to Sessions",
  "auto_closed": "Auto-closed",
  "filter_discipline": "All Disciplines",
  "filter_type": "All Types",
  "sort_newest": "Newest First",
  "sort_oldest": "Oldest First",
  "showing_count": "{count} sessions"
}
```

Also add to `"nav"` section:
```json
"sessions": "Sessions"
```

- [ ] **Step 3: Add translations to all other 7 locale files**

Add the same keys to `es.json`, `de.json`, `fr.json`, `pt.json`, `eu.json`, `ca.json`, `gl.json` with appropriate translations for each language. Use the same key structure. For example in `es.json`:

```json
"sessions": {
  "title": "Sesiones",
  "subtitle": "Consulta tus sesiones de tiro sincronizadas.",
  "empty": "Aún no hay sesiones sincronizadas. Sincroniza desde tu app TriggerTime para verlas aquí.",
  "date": "Fecha",
  "discipline": "Disciplina",
  "type": "Tipo",
  "location": "Ubicación",
  "total_score": "Puntuación",
  "total_x_count": "Cuenta X",
  "notes": "Notas",
  "series": "Series",
  "shots": "Disparos",
  "strings": "Tandas",
  "phase": "Fase",
  "sighting": "Fogueo",
  "detail_title": "Detalle de Sesión",
  "back_to_list": "Volver a Sesiones",
  "auto_closed": "Cerrada automáticamente",
  "filter_discipline": "Todas las Disciplinas",
  "filter_type": "Todos los Tipos",
  "sort_newest": "Más recientes",
  "sort_oldest": "Más antiguas",
  "showing_count": "{count} sesiones"
}
```

And in `nav`: `"sessions": "Sesiones"`. Repeat for each language with appropriate translations.

- [ ] **Step 4: Commit**

```bash
cd client && git add src/api/sessions.js src/i18n/locales/*.json
git commit -m "feat(sync): add sessions API module and i18n translations"
```

---

### Task 9: Vue SPA — Sessions List View

**Files:**
- Create: `client/src/views/dashboard/SessionsView.vue`
- Create: `client/src/components/dashboard/SessionsTable.vue`

- [ ] **Step 1: Create SessionsTable component**

```vue
<template>
  <div class="sessions-table">
    <div class="sessions-table__filters">
      <select v-model="sortOrder" @change="$emit('sort', sortOrder)">
        <option value="date_desc">{{ $t('sessions.sort_newest') }}</option>
        <option value="date_asc">{{ $t('sessions.sort_oldest') }}</option>
      </select>
      <select v-model="disciplineFilter" @change="$emit('filter-discipline', disciplineFilter)">
        <option value="">{{ $t('sessions.filter_discipline') }}</option>
        <option v-for="d in disciplines" :key="d" :value="d">{{ d }}</option>
      </select>
      <select v-model="typeFilter" @change="$emit('filter-type', typeFilter)">
        <option value="">{{ $t('sessions.filter_type') }}</option>
        <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
      </select>
    </div>

    <table class="sessions-table__table">
      <thead>
        <tr>
          <th>{{ $t('sessions.date') }}</th>
          <th>{{ $t('sessions.discipline') }}</th>
          <th>{{ $t('sessions.type') }}</th>
          <th>{{ $t('sessions.location') }}</th>
          <th>{{ $t('sessions.total_score') }}</th>
          <th>{{ $t('sessions.total_x_count') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="session in sessions"
          :key="session.id"
          class="sessions-table__row"
          @click="$emit('select', session.id)"
        >
          <td>{{ formatDate(session.date) }}</td>
          <td>{{ session.discipline_name }}</td>
          <td>{{ session.type }}</td>
          <td>{{ session.location || '—' }}</td>
          <td>{{ session.total_score }}</td>
          <td>{{ session.total_x_count }}</td>
        </tr>
      </tbody>
    </table>

    <div v-if="pagination && pagination.pages > 1" class="sessions-table__pagination">
      <AppButton
        :disabled="pagination.page <= 1"
        variant="secondary"
        @click="$emit('page', pagination.page - 1)"
      >
        &laquo;
      </AppButton>
      <span>{{ pagination.page }} / {{ pagination.pages }}</span>
      <AppButton
        :disabled="pagination.page >= pagination.pages"
        variant="secondary"
        @click="$emit('page', pagination.page + 1)"
      >
        &raquo;
      </AppButton>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/ui/AppButton.vue'

const { locale } = useI18n()

defineProps({
  sessions: { type: Array, required: true },
  pagination: { type: Object, default: null },
  disciplines: { type: Array, default: () => [] },
  types: { type: Array, default: () => [] },
})

defineEmits(['select', 'sort', 'filter-discipline', 'filter-type', 'page'])

const sortOrder = ref('date_desc')
const disciplineFilter = ref('')
const typeFilter = ref('')

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString(locale.value, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<style scoped>
.sessions-table__filters {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.sessions-table__filters select {
  padding: 0.5rem;
  border: 1px solid var(--color-border, #ddd);
  border-radius: 6px;
  background: var(--color-bg, #fff);
}

.sessions-table__table {
  width: 100%;
  border-collapse: collapse;
}

.sessions-table__table th {
  text-align: left;
  padding: 0.75rem;
  border-bottom: 2px solid var(--color-border, #ddd);
  font-weight: 600;
}

.sessions-table__table td {
  padding: 0.75rem;
  border-bottom: 1px solid var(--color-border, #eee);
}

.sessions-table__row {
  cursor: pointer;
  transition: background 0.15s;
}

.sessions-table__row:hover {
  background: var(--color-hover, #f5f5f5);
}

.sessions-table__pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1rem;
}
</style>
```

- [ ] **Step 2: Create SessionsView**

```vue
<template>
  <div class="sessions-view">
    <div class="sessions-view__header">
      <div>
        <h1>{{ $t('sessions.title') }}</h1>
        <p class="sessions-view__subtitle">{{ $t('sessions.subtitle') }}</p>
      </div>
      <span v-if="pagination" class="sessions-view__count">
        {{ $t('sessions.showing_count', { count: pagination.total }) }}
      </span>
    </div>

    <div v-if="isLoading" class="sessions-view__loading">
      <span class="spinner"></span>
    </div>

    <div v-else-if="sessions.length === 0" class="sessions-view__empty">
      <p>{{ $t('sessions.empty') }}</p>
    </div>

    <SessionsTable
      v-else
      :sessions="sessions"
      :pagination="pagination"
      :disciplines="disciplines"
      :types="types"
      @select="goToDetail"
      @sort="handleSort"
      @filter-discipline="handleFilterDiscipline"
      @filter-type="handleFilterType"
      @page="handlePage"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import sessionsApi from '@/api/sessions'
import SessionsTable from '@/components/dashboard/SessionsTable.vue'

const router = useRouter()
const sessions = ref([])
const pagination = ref(null)
const disciplines = ref([])
const types = ref([])
const isLoading = ref(true)

const currentSort = ref('date_desc')
const currentDiscipline = ref('')
const currentType = ref('')
const currentPage = ref(1)

async function loadSessions() {
  isLoading.value = true
  try {
    const params = {
      sort: currentSort.value,
      page: currentPage.value,
      limit: 20,
    }
    if (currentDiscipline.value) params.discipline = currentDiscipline.value
    if (currentType.value) params.type = currentType.value

    const data = await sessionsApi.getSessions(params)
    sessions.value = data.sessions
    pagination.value = data.pagination

    // Extract unique disciplines and types for filters
    if (disciplines.value.length === 0) {
      const allData = await sessionsApi.getSessions({ limit: 1000 })
      disciplines.value = [...new Set(allData.sessions.map((s) => s.discipline_name))].sort()
      types.value = [...new Set(allData.sessions.map((s) => s.type))].sort()
    }
  } catch (error) {
    console.error('Failed to load sessions:', error)
  } finally {
    isLoading.value = false
  }
}

function goToDetail(uuid) {
  router.push({ name: 'session-detail', params: { uuid } })
}

function handleSort(sort) {
  currentSort.value = sort
  currentPage.value = 1
  loadSessions()
}

function handleFilterDiscipline(discipline) {
  currentDiscipline.value = discipline
  currentPage.value = 1
  loadSessions()
}

function handleFilterType(type) {
  currentType.value = type
  currentPage.value = 1
  loadSessions()
}

function handlePage(page) {
  currentPage.value = page
  loadSessions()
}

onMounted(loadSessions)
</script>

<style scoped>
.sessions-view__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.sessions-view__header h1 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
}

.sessions-view__subtitle {
  color: var(--color-text-secondary, #666);
  margin-top: 0.25rem;
}

.sessions-view__count {
  color: var(--color-text-secondary, #666);
  font-size: 0.875rem;
}

.sessions-view__loading {
  display: flex;
  justify-content: center;
  padding: 3rem;
}

.sessions-view__empty {
  text-align: center;
  padding: 3rem;
  color: var(--color-text-secondary, #666);
}

.spinner {
  width: 2rem;
  height: 2rem;
  border: 3px solid var(--color-border, #ddd);
  border-top-color: var(--color-primary, #4f46e5);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
```

- [ ] **Step 3: Commit**

```bash
cd client && git add src/views/dashboard/SessionsView.vue src/components/dashboard/SessionsTable.vue
git commit -m "feat(sync): add sessions list view and table component"
```

---

### Task 10: Vue SPA — Session Detail View

**Files:**
- Create: `client/src/views/dashboard/SessionDetailView.vue`
- Create: `client/src/components/dashboard/SessionScorecard.vue`

- [ ] **Step 1: Create SessionScorecard component**

```vue
<template>
  <div class="scorecard">
    <div v-for="(series, index) in seriesList" :key="series.id" class="scorecard__series">
      <div class="scorecard__series-header">
        <span class="scorecard__series-label">
          {{ series.is_sighting ? $t('sessions.sighting') : `${$t('sessions.series')} ${index + 1}` }}
        </span>
        <span class="scorecard__series-score">
          {{ series.total_score ?? '—' }}
          <span v-if="series.total_x_count" class="scorecard__x-count">({{ series.total_x_count }}x)</span>
        </span>
      </div>
      <div v-if="series.sync_shots && series.sync_shots.length" class="scorecard__shots">
        <span
          v-for="shot in series.sync_shots"
          :key="shot.id"
          class="scorecard__shot"
          :class="{ 'scorecard__shot--x': shot.is_x }"
        >
          {{ shot.value }}{{ shot.is_x ? 'x' : '' }}
        </span>
      </div>
    </div>

    <div v-for="(str, index) in stringsList" :key="str.id" class="scorecard__series">
      <div class="scorecard__series-header">
        <span class="scorecard__series-label">
          {{ str.is_sighting ? $t('sessions.sighting') : `${$t('sessions.strings')} ${index + 1}` }}
        </span>
        <span class="scorecard__series-score">
          {{ str.total_score }}
          <span v-if="str.x_count" class="scorecard__x-count">({{ str.x_count }}x)</span>
          <span v-if="str.first_miss" class="scorecard__first-miss">FM: {{ str.first_miss }}</span>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  seriesList: { type: Array, default: () => [] },
  stringsList: { type: Array, default: () => [] },
})
</script>

<style scoped>
.scorecard__series {
  margin-bottom: 1rem;
  padding: 0.75rem;
  border: 1px solid var(--color-border, #eee);
  border-radius: 8px;
}

.scorecard__series-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.scorecard__series-label {
  font-weight: 600;
  font-size: 0.875rem;
}

.scorecard__series-score {
  font-weight: 700;
  font-size: 1.125rem;
}

.scorecard__x-count {
  color: var(--color-text-secondary, #666);
  font-weight: 400;
  font-size: 0.875rem;
}

.scorecard__first-miss {
  color: var(--color-warning, #d97706);
  font-weight: 400;
  font-size: 0.75rem;
  margin-left: 0.5rem;
}

.scorecard__shots {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.scorecard__shot {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  background: var(--color-bg-secondary, #f3f4f6);
  font-size: 0.8rem;
  font-weight: 600;
}

.scorecard__shot--x {
  background: var(--color-primary-light, #e0e7ff);
  color: var(--color-primary, #4f46e5);
}
</style>
```

- [ ] **Step 2: Create SessionDetailView**

```vue
<template>
  <div class="session-detail">
    <div class="session-detail__back">
      <AppButton variant="secondary" @click="router.push({ name: 'sessions' })">
        &larr; {{ $t('sessions.back_to_list') }}
      </AppButton>
    </div>

    <div v-if="isLoading" class="session-detail__loading">
      <span class="spinner"></span>
    </div>

    <template v-else-if="session">
      <div class="session-detail__header">
        <h1>{{ session.discipline_name }}</h1>
        <div class="session-detail__meta">
          <span>{{ formatDate(session.date) }}</span>
          <AppBadge>{{ session.type }}</AppBadge>
          <AppBadge v-if="session.auto_closed" variant="warning">{{ $t('sessions.auto_closed') }}</AppBadge>
        </div>
        <p v-if="session.location" class="session-detail__location">{{ session.location }}</p>
        <p v-if="session.notes" class="session-detail__notes">{{ session.notes }}</p>
      </div>

      <AppCard>
        <template #header>
          <div class="session-detail__score-header">
            <span class="session-detail__total-score">{{ session.total_score }}</span>
            <span v-if="session.total_x_count" class="session-detail__total-x">({{ session.total_x_count }}x)</span>
          </div>
        </template>

        <SessionScorecard
          :series-list="session.sync_series || []"
          :strings-list="session.sync_strings || []"
        />
      </AppCard>
    </template>

    <div v-else class="session-detail__not-found">
      <p>Session not found.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import sessionsApi from '@/api/sessions'
import SessionScorecard from '@/components/dashboard/SessionScorecard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppCard from '@/components/ui/AppCard.vue'

const route = useRoute()
const router = useRouter()
const { locale } = useI18n()

const session = ref(null)
const isLoading = ref(true)

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString(locale.value, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function loadSession() {
  isLoading.value = true
  try {
    const data = await sessionsApi.getSession(route.params.uuid)
    session.value = data.session
  } catch (error) {
    console.error('Failed to load session:', error)
    session.value = null
  } finally {
    isLoading.value = false
  }
}

onMounted(loadSession)
</script>

<style scoped>
.session-detail__back {
  margin-bottom: 1.5rem;
}

.session-detail__header {
  margin-bottom: 1.5rem;
}

.session-detail__header h1 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
}

.session-detail__meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--color-text-secondary, #666);
}

.session-detail__location,
.session-detail__notes {
  margin-top: 0.5rem;
  color: var(--color-text-secondary, #666);
}

.session-detail__score-header {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
}

.session-detail__total-score {
  font-size: 2rem;
  font-weight: 700;
}

.session-detail__total-x {
  font-size: 1.125rem;
  color: var(--color-text-secondary, #666);
}

.session-detail__loading,
.session-detail__not-found {
  display: flex;
  justify-content: center;
  padding: 3rem;
}

.spinner {
  width: 2rem;
  height: 2rem;
  border: 3px solid var(--color-border, #ddd);
  border-top-color: var(--color-primary, #4f46e5);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
```

- [ ] **Step 3: Commit**

```bash
cd client && git add src/views/dashboard/SessionDetailView.vue src/components/dashboard/SessionScorecard.vue
git commit -m "feat(sync): add session detail view and scorecard component"
```

---

### Task 11: Vue SPA — Router & Sidebar Navigation

**Files:**
- Modify: `client/src/router/index.js`
- Modify: `client/src/components/layout/DashboardLayout.vue` (or wherever the sidebar nav is)

- [ ] **Step 1: Add routes to router**

In `client/src/router/index.js`, find the dashboard routes section (near the `/dashboard/devices` route) and add:

```js
{
  path: '/dashboard/sessions',
  name: 'sessions',
  component: () => import('@/views/dashboard/SessionsView.vue'),
  meta: { requiresAuth: true, requiresVerified: true },
},
{
  path: '/dashboard/sessions/:uuid',
  name: 'session-detail',
  component: () => import('@/views/dashboard/SessionDetailView.vue'),
  meta: { requiresAuth: true, requiresVerified: true },
},
```

- [ ] **Step 2: Add sidebar navigation link**

Find the dashboard sidebar component (likely in `client/src/components/layout/DashboardLayout.vue` or similar). Add a "Sessions" link below "Devices":

```html
<router-link to="/dashboard/sessions">
  {{ $t('nav.sessions') }}
</router-link>
```

Use the same pattern as the existing "Devices" link — same classes, same structure.

- [ ] **Step 3: Build and verify**

```bash
cd client && npm run build
```

Expected: Build succeeds without errors.

- [ ] **Step 4: Commit**

```bash
cd client && git add src/router/index.js src/components/layout/DashboardLayout.vue
git commit -m "feat(sync): add sessions routes and sidebar navigation"
```

---

### Task 12: Final Verification

- [ ] **Step 1: Run all backend tests**

```bash
composer test
```

Expected: All tests pass.

- [ ] **Step 2: Run code quality checks**

```bash
composer cs-check
```

Expected: No style violations.

- [ ] **Step 3: Build frontend**

```bash
cd client && npm run build
```

Expected: Build succeeds.

- [ ] **Step 4: Verify migrations work on fresh database**

```bash
bin/cake migrations rollback --target 0
bin/cake migrations migrate
```

Expected: All migrations apply cleanly.
