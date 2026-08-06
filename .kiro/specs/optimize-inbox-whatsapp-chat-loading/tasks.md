# Implementation Tasks: Optimize Inbox WhatsApp Chat Loading

## Task Breakdown

Tasks dibagi berdasarkan dependency dan prioritas. Setiap task reference ke requirement dan design decision yang relevan.

---

## Phase 1: Measurement & Baseline (Requirement 1, 7)

### Task 1.1: Implement Timing Instrumentation Helper

**Requirement**: REQ-1, REQ-7  
**Design Section**: 2.5 Performance Monitoring Strategy  
**Files**: `src/app/Filament/Pages/InboxWhatsapp.php`

**Implementation**:
- [x] Add private method `measureTime(string $operation, callable $callback): mixed`
- [x] Integrate dengan Laravel Log::debug() untuk timing output
- [x] Add Laravel Debugbar integration via `app('debugbar')->addMeasure()`
- [x] Add warning log untuk operations >500ms threshold
- [x] Format log output: `['operation', 'chat_id', 'duration_ms']`

**Validation**:
```bash
# Test logging
php artisan tinker
>>> $page = \Livewire\Livewire::test(\App\Filament\Pages\InboxWhatsapp::class);
>>> $page->call('selectChat', '<test-chat-id>');
>>> // Check storage/logs/laravel.log untuk timing entries
```

**Dependencies**: None  
**Estimated Time**: 1 hour  
**Status**: ✅ COMPLETED

---

### Task 1.2: Wrap Main Operations dengan Timing

**Requirement**: REQ-1, REQ-7  
**Design Section**: 2.5 Performance Monitoring Strategy  
**Files**: `src/app/Filament/Pages/InboxWhatsapp.php`

**Implementation**:
- [x] Wrap `selectChat()` dengan `measureTime('selectChat.total', ...)`
- [x] Wrap `loadChatMessages()` dengan `measureTime('selectChat.loadMessages', ...)`
- [x] Wrap `loadHistoryChats()` dengan `measureTime('selectChat.loadHistory', ...)`
- [x] Wrap `loadInternalNotes()` dengan `measureTime('selectChat.loadNotes', ...)`
- [x] Ensure nested timing tidak double-count (use separate timers)

**Validation**:
```bash
# Check log breakdown
tail -f storage/logs/laravel.log | grep "InboxWhatsApp timing"
```

**Dependencies**: Task 1.1  
**Estimated Time**: 30 minutes  
**Status**: ✅ COMPLETED

---

### Task 1.3: Create Benchmark Artisan Command

**Requirement**: REQ-7  
**Design Section**: 4.2 Command: VpointBenchmarkInbox  
**Files**: `src/app/Console/Commands/VpointBenchmarkInbox.php`

**Implementation**:
- [x] Create command class: `php artisan make:command VpointBenchmarkInbox`
- [x] Add signature: `vpoint:benchmark-inbox {--messages=100} {--iterations=10}`
- [x] Implement chat fixture creation dengan TChat + TChatD records
- [x] Implement benchmark loop: measure selectChat() durasi dan query count
- [x] Output statistics: mean, min, max, P50, P95 duration
- [x] Cleanup fixture setelah benchmark selesai

**Validation**:
```bash
php artisan vpoint:benchmark-inbox --messages=50 --iterations=10
# Expected output: Average duration, query count, percentiles
```

**Dependencies**: Task 1.2  
**Estimated Time**: 3 hours  
**Status**: ✅ COMPLETED

---

## Phase 2: Database Optimization (Requirement 2, 5, 8)

### Task 2.1: Create Schema Cache Helper Method

**Requirement**: REQ-2 (AC5, AC6)  
**Design Section**: 2.1 Architecture - Schema Column Cache  
**Files**: `src/app/Filament/Pages/InboxWhatsapp.php`

**Implementation**:
- [x] Add `private static array $schemaCache = [];` property di class
- [x] Add method `private static function hasCol(string $table, string $column): bool`
- [x] Implement dengan null coalescing: `return self::$schemaCache["$table.$column"] ??= Schema::hasColumn($table, $column);`
- [x] Replace semua call `Schema::hasColumn()` dengan `self::hasCol()`

**Validation**:
```bash
php -l app/Filament/Pages/InboxWhatsapp.php
# Run benchmark: query count should reduce by ~5 queries
php artisan vpoint:benchmark-inbox --messages=100
```

**Dependencies**: Task 1.3  
**Estimated Time**: 1 hour  
**Status**: ✅ COMPLETED

---

### Task 2.2: Create Database Index Migration

**Requirement**: REQ-8  
**Design Section**: 4.1 Migration: CreateInboxPerformanceIndexes  
**Files**: `src/database/migrations/2026_08_02_000001_create_inbox_performance_indexes.php`

**Implementation**:
- [x] Create migration: `php artisan make:migration create_inbox_performance_indexes`
- [x] Add driver check: `if (DB::connection()->getDriverName() !== 'sqlsrv') return;`
- [x] Implement up(): Create 5 indexes dengan IF NOT EXISTS:
  - `IX_TChatD_IdChat_TglPesan ON TChatD(IdChat, TglPesan)`
  - `IX_TChat_TglChatTerakhir ON TChat(TglChatTerakhir DESC)`
  - `IX_TChat_IdCustomer ON TChat(IdCustomer)`
  - `IX_TChat_IdInstansi ON TChat(IdInstansi)`
  - `IX_TChat_IdNomorWhatsapp ON TChat(IdNomorWhatsapp)`
- [x] Add `WITH (ONLINE = ON, SORT_IN_TEMPDB = ON)` untuk index TChatD
- [x] Implement down(): Drop indexes dengan IF EXISTS
- [x] Test rollback: `php artisan migrate:rollback --step=1`

**Validation**:
```bash
# Dry-run on test database
php artisan migrate --pretend

# Real migration on staging
php artisan migrate

# Verify indexes created
php artisan tinker
>>> DB::select("SELECT name FROM sys.indexes WHERE object_id = OBJECT_ID('TChatD')");
```

**Dependencies**: None  
**Estimated Time**: 2 hours  
**Status**: ✅ COMPLETED (migration created, not yet executed)

---

### Task 2.3: Verify Index Usage dengan SQL Server Execution Plan

**Requirement**: REQ-2 (AC1, AC2)  
**Design Section**: 3.2 Database Index Usage  
**Files**: N/A (SQL Server analysis)

**Implementation**:
- [ ] Connect to SQL Server Management Studio (SSMS)
- [ ] Enable "Include Actual Execution Plan" (Ctrl+M)
- [ ] Run query:
  ```sql
  SELECT * FROM TChatD 
  WHERE IdChat = '<test-chat-id>' 
  ORDER BY TglPesan ASC
  ```
- [ ] Verify execution plan shows:
  - **Index Seek** pada `IX_TChatD_IdChat_TglPesan`
  - **NOT** Clustered Index Scan atau Table Scan
  - **NOT** Sort operator (sorting via index)
- [ ] Screenshot execution plan untuk dokumentasi

**Validation**:
- Index Seek cost < 20% total query cost
- No Sort operator present
- Estimated rows match actual rows (statistics accurate)

**Dependencies**: Task 2.2  
**Estimated Time**: 1 hour

---

## Phase 3: Message Pagination (Requirement 4)

### Task 3.1: Add Pagination Properties ke InboxWhatsapp

**Requirement**: REQ-4 (AC1, AC6)  
**Design Section**: 2.2 Message Pagination Strategy  
**Files**: `src/app/Filament/Pages/InboxWhatsapp.php`

**Implementation**:
- [x] Add `public int $messageLimit = 50;` property
- [x] Add `public int $messageOffset = 0;` property
- [x] Add `public bool $allMessagesLoaded = false;` property
- [x] Initialize properties pada `mount()` atau `selectChat()`

**Validation**:
```bash
php -l app/Filament/Pages/InboxWhatsapp.php
```

**Dependencies**: None  
**Estimated Time**: 15 minutes  
**Status**: ✅ COMPLETED

---

### Task 3.2: Refactor selectChat() untuk Initial Load 50 Messages

**Requirement**: REQ-4 (AC1)  
**Design Section**: 2.2 Message Pagination Strategy  
**Files**: `src/app/Filament/Pages/InboxWhatsapp.php`

**Implementation**:
- [ ] Extract query logic dari `selectChat()` ke method baru `loadChatMessages(bool $append = false)`
- [ ] Dalam `loadChatMessages()`:
  - Query dengan `->offset($this->messageOffset)->limit($this->messageLimit)`
  - Change `->orderBy('d.TglPesan')` menjadi `->orderByDesc('d.TglPesan')` untuk get latest first
  - Reverse hasil query sebelum map: `$messages->reverse()`
  - Set `$this->allMessagesLoaded = true` jika row count < limit
- [ ] Update `selectChat()` untuk call `loadChatMessages()` tanpa append
- [ ] Reset offset dan flag pada `selectChat()`: `$this->messageOffset = 0; $this->allMessagesLoaded = false;`

**Validation**:
```bash
php artisan test --filter=InboxWhatsappTest
# Manual: Open chat, verify hanya 50 pesan terbaru yang muncul
```

**Dependencies**: Task 3.1  
**Estimated Time**: 2 hours

---

### Task 3.3: Implement loadMoreMessages() Livewire Method

**Requirement**: REQ-4 (AC2, AC3, AC4)  
**Design Section**: 2.2 Message Pagination Strategy  
**Files**: `src/app/Filament/Pages/InboxWhatsapp.php`

**Implementation**:
- [ ] Add `public function loadMoreMessages(): void` method
- [ ] Early return jika `$this->allMessagesLoaded` atau `!$this->selectedChatId`
- [ ] Increment offset: `$this->messageOffset += $this->messageLimit;`
- [ ] Call `$this->loadChatMessages(append: true)`
- [ ] Dalam `loadChatMessages(append: true)`:
  - Prepend hasil ke `$this->messages`: `$this->messages = array_merge($formatted, $this->messages);`

**Validation**:
```bash
# Test via Livewire
php artisan tinker
>>> $page = \Livewire\Livewire::test(\App\Filament\Pages\InboxWhatsapp::class);
>>> $page->set('selectedChatId', '<test-id>')->call('selectChat', '<test-id>');
>>> $initialCount = count($page->get('messages'));
>>> $page->call('loadMoreMessages');
>>> $newCount = count($page->get('messages'));
>>> assert($newCount > $initialCount, 'Messages should increase after loadMore');
```

**Dependencies**: Task 3.2  
**Estimated Time**: 1.5 hours

---

### Task 3.4: Update Blade View untuk Lazy Loading UI

**Requirement**: REQ-4 (AC5), REQ-6 (AC2, AC5)  
**Design Section**: 2.4 Frontend Rendering Optimization  
**Files**: `src/resources/views/filament/pages/inbox-whatsapp.blade.php`

**Implementation**:
- [ ] Wrap messages container dengan Alpine.js x-data:
  ```blade
  x-data="{ 
      loadingMore: false,
      async loadMore() {
          if (this.loadingMore || @js($allMessagesLoaded)) return;
          this.loadingMore = true;
          await @this.call('loadMoreMessages');
          this.loadingMore = false;
      }
  }"
  ```
- [ ] Add scroll event listener:
  ```blade
  x-on:scroll="if ($el.scrollTop < 100 && !loadingMore) loadMore()"
  ```
- [ ] Add loading indicator di atas messages:
  ```blade
  <div x-show="loadingMore" x-cloak class="text-center py-2">
      <span>{{ __('ui.pages.inbox.loading_more') }}</span>
  </div>
  ```
- [ ] Replace `@foreach` dengan Alpine.js `<template x-for>`:
  ```blade
  <template x-for="message in @js($messages)" :key="message.Id">
      <div class="message-bubble">...</div>
  </template>
  ```
- [ ] Add `loading="lazy"` pada image elements:
  ```blade
  <img :src="message.MediaUrl" loading="lazy" />
  ```

**Validation**:
```bash
# Manual testing:
# 1. Open chat with >50 messages
# 2. Scroll to top
# 3. Verify "Loading more..." indicator appears
# 4. Verify older messages load
# 5. Check browser console for errors
```

**Dependencies**: Task 3.3  
**Estimated Time**: 2 hours

---

## Phase 4: Post-Processing Optimization (Requirement 3)

### Task 4.1: Implement Lazy Media Evaluation Guard

**Requirement**: REQ-3 (AC2, AC3, AC5)  
**Design Section**: 2.3 Media Processing Lazy Evaluation  
**Files**: `src/app/Filament/Pages/InboxWhatsapp.php`

**Implementation**:
- [ ] Dalam `loadChatMessages()->map()`, add guard clause sebelum media inspection:
  ```php
  $hasMedia = filled($row->UrlMedia) 
      || filled($row->TipeMime) 
      || filled($row->NamaFileMedia)
      || ($row->JenisPesan && !in_array($row->JenisPesan, ['text', 'chat'], true));
  
  if (!$hasMedia) {
      return [
          'Id' => $row->Id,
          'IsiPesan' => $row->IsiPesan,
          'MediaCategory' => null,
          'MediaUrl' => null,
          'MediaLabel' => null,
          // ... non-media fields
      ];
  }
  ```
- [ ] Only call `WahaMediaPayload::inspectPayload()` inside the `if ($hasMedia)` block
- [ ] Skip call `base64TextPayload()`, `mediaPresentationCategory()`, `mediaLabel()` untuk non-media

**Validation**:
```bash
# Benchmark should show improvement
php artisan vpoint:benchmark-inbox --messages=100
# Compare duration dengan sebelum optimization
```

**Dependencies**: Task 3.2  
**Estimated Time**: 1 hour

---

## Phase 5: Testing & Validation (Requirement 9)

### Task 5.1: Create Performance Test Class

**Requirement**: REQ-9  
**Design Section**: 4.3 Test: InboxWhatsappPerformanceTest  
**Files**: `src/tests/Feature/InboxWhatsappPerformanceTest.php`

**Implementation**:
- [ ] Create test class: `php artisan make:test InboxWhatsappPerformanceTest`
- [ ] Implement `test_select_chat_loads_within_time_limit()`:
  - Create chat fixture with 100 messages
  - Measure `selectChat()` duration
  - Assert duration <1000ms
- [ ] Implement `test_select_chat_uses_minimal_queries()`:
  - Enable query log
  - Call selectChat()
  - Assert query count ≤5
  - Assert no N+1 pattern (no queries inside loop)
- [ ] Implement helper `assertChatLoadsWithin(int $ms, string $chatId)`
- [ ] Implement helper `assertNoNPlusOnePattern(array $queries)`

**Validation**:
```bash
php artisan test --filter=InboxWhatsappPerformanceTest
# All tests should pass
```

**Dependencies**: Task 3.3, Task 4.1  
**Estimated Time**: 3 hours

---

### Task 5.2: Add PHPUnit Assertion Helpers

**Requirement**: REQ-9 (AC5)  
**Design Section**: 4.3 Test Implementation  
**Files**: `src/tests/Feature/InboxWhatsappPerformanceTest.php`

**Implementation**:
- [ ] Implement `assertChatLoadsWithin(int $ms, string $chatId)`:
  ```php
  private function assertChatLoadsWithin(int $ms, string $chatId): void
  {
      $start = microtime(true);
      $page = Livewire::test(InboxWhatsapp::class);
      $page->call('selectChat', $chatId);
      $duration = (microtime(true) - $start) * 1000;
      
      $this->assertLessThan($ms, $duration,
          "Chat loading took {$duration}ms, expected <{$ms}ms");
  }
  ```
- [ ] Implement `assertNoNPlusOnePattern(array $queries)`:
  ```php
  private function assertNoNPlusOnePattern(array $queries): void
  {
      // Check untuk pattern: multiple similar queries dengan hanya ID berbeda
      $querySignatures = array_map(fn($q) => preg_replace('/\d+/', '?', $q['query']), $queries);
      $duplicates = array_count_values($querySignatures);
      
      foreach ($duplicates as $signature => $count) {
          $this->assertLessThanOrEqual(2, $count,
              "Possible N+1 detected: Query executed {$count} times: {$signature}");
      }
  }
  ```

**Validation**:
```bash
php artisan test --filter=assertChatLoadsWithin
php artisan test --filter=assertNoNPlusOnePattern
```

**Dependencies**: Task 5.1  
**Estimated Time**: 1.5 hours

---

### Task 5.3: Manual Testing Checklist Execution

**Requirement**: REQ-1, REQ-2, REQ-4, REQ-6  
**Design Section**: 7.4 Manual Testing  
**Files**: N/A

**Implementation**:
- [ ] Test 1: Open chat dengan 10 pesan → verify load time <500ms
- [ ] Test 2: Open chat dengan 100 pesan → verify initial 50 pesan load <1000ms
- [ ] Test 3: Scroll to top → verify "Loading more..." indicator
- [ ] Test 4: Load more messages → verify older messages prepended
- [ ] Test 5: Verify media images lazy load (check Network tab)
- [ ] Test 6: Check browser console untuk errors
- [ ] Test 7: Verify chat dengan text-only messages skip media processing (check timing log)
- [ ] Test 8: Test pada SQL Server staging database
- [ ] Test 9: Verify Laravel Debugbar timeline shows timing breakdown
- [ ] Test 10: Verify log file contains timing entries

**Validation**:
- Create manual testing report document
- Screenshot evidence untuk setiap test case
- Log timing output samples

**Dependencies**: All previous tasks  
**Estimated Time**: 3 hours

---

## Phase 6: Documentation & Deployment

### Task 6.1: Update AGENTS.md atau README dengan Performance Notes

**Requirement**: REQ-7  
**Design Section**: N/A  
**Files**: `AGENTS.md` atau `openspec/changes/optimize-inbox-whatsapp-chat-loading/README.md`

**Implementation**:
- [ ] Document index yang dibuat dan tujuannya
- [ ] Document pagination behavior (50 initial, lazy load)
- [ ] Document `vpoint:benchmark-inbox` command usage
- [ ] Document monitoring metric (`inbox.chat_load_duration_ms`)
- [ ] Document rollback procedure jika performance degrades

**Validation**:
- Code review documentation clarity
- Ensure no sensitive information disclosed

**Dependencies**: All previous tasks  
**Estimated Time**: 1 hour

---

### Task 6.2: Create Deployment Checklist

**Requirement**: N/A (Deployment safety)  
**Design Section**: 6. Deployment Strategy  
**Files**: `openspec/changes/optimize-inbox-whatsapp-chat-loading/DEPLOYMENT.md`

**Implementation**:
- [ ] Create deployment checklist document:
  - Pre-deployment: Backup database, verify staging tests
  - Migration: Run index migration saat off-peak hours
  - Code deployment: Deploy timing + pagination code
  - Post-deployment: Monitor logs, query P95 duration
  - Rollback triggers: P95 >2s, error rate >5%
- [ ] Schedule maintenance window untuk migration
- [ ] Notify team tentang expected downtime (jika ada)

**Validation**:
- Review checklist dengan team lead
- Dry-run deployment pada staging environment

**Dependencies**: Task 6.1  
**Estimated Time**: 1 hour

---

### Task 6.3: Execute Deployment ke Production

**Requirement**: N/A (Deployment execution)  
**Design Section**: 6.1 Rollout Plan  
**Files**: N/A

**Implementation**:
- [ ] **Phase 1: Migration** (Maintenance window):
  - Backup database: `BACKUP DATABASE WACS TO DISK='...';`
  - Run migration: `php artisan migrate --force`
  - Verify indexes created: Check sys.indexes
  - Monitor lock waits: `sp_who2`
- [ ] **Phase 2: Code Deployment**:
  - Deploy code via git pull + composer install
  - Clear cache: `php artisan optimize:clear`
  - Restart queue workers (if using)
  - Restart PHP-FPM/Octane
- [ ] **Phase 3: Monitoring** (First 24 hours):
  - Monitor Debugbar/APM untuk P50, P95 duration
  - Check error logs untuk exceptions
  - Monitor user feedback/complaints
  - Watch database CPU/IO metrics
- [ ] **Phase 4: Gradual Rollout** (Optional A/B test):
  - Enable pagination untuk 50% users via feature flag
  - Compare metrics after 48 hours
  - Full rollout jika metrics positive

**Validation**:
- P95 loading time <1.5s (target met)
- No error spike pada monitoring dashboard
- User feedback positive atau neutral

**Dependencies**: Task 6.2  
**Estimated Time**: 4 hours (includes monitoring)

---

## Summary & Dependencies Graph

```
Phase 1 (Measurement):
├── Task 1.1 (Timing Helper)
├── Task 1.2 (Wrap Operations) → depends on 1.1
└── Task 1.3 (Benchmark Command) → depends on 1.2

Phase 2 (Database):
├── Task 2.1 (Schema Cache) → depends on 1.3
├── Task 2.2 (Index Migration) → independent
└── Task 2.3 (Verify Index) → depends on 2.2

Phase 3 (Pagination):
├── Task 3.1 (Properties) → independent
├── Task 3.2 (Refactor selectChat) → depends on 3.1
├── Task 3.3 (loadMoreMessages) → depends on 3.2
└── Task 3.4 (Blade UI) → depends on 3.3

Phase 4 (Optimization):
└── Task 4.1 (Lazy Media) → depends on 3.2

Phase 5 (Testing):
├── Task 5.1 (Performance Test) → depends on 3.3, 4.1
├── Task 5.2 (Assertion Helpers) → depends on 5.1
└── Task 5.3 (Manual Testing) → depends on all

Phase 6 (Deployment):
├── Task 6.1 (Documentation) → depends on all Phase 5
├── Task 6.2 (Deployment Checklist) → depends on 6.1
└── Task 6.3 (Execute Deployment) → depends on 6.2
```

## Total Estimated Time

| Phase | Tasks | Time |
|-------|-------|------|
| Phase 1: Measurement | 3 | 4.5 hours |
| Phase 2: Database | 3 | 4 hours |
| Phase 3: Pagination | 4 | 6 hours |
| Phase 4: Optimization | 1 | 1 hour |
| Phase 5: Testing | 3 | 7.5 hours |
| Phase 6: Deployment | 3 | 6 hours |
| **Total** | **17 tasks** | **29 hours** (~4 days) |

## Implementation Sequence Recommendation

**Week 1** (Foundation):
- Day 1-2: Phase 1 + Phase 2 (Measurement + Indexes)
- Day 3: Run benchmark, gather baseline metrics

**Week 2** (Optimization):
- Day 1-2: Phase 3 (Pagination implementation)
- Day 3: Phase 4 (Media optimization)

**Week 3** (Testing & Deploy):
- Day 1-2: Phase 5 (Testing + validation)
- Day 3: Phase 6 (Documentation + staging deploy)

**Week 4** (Production):
- Day 1: Production deployment + monitoring
- Day 2-5: Monitor metrics, iterate jika perlu

---

**Tasks Document Complete**  
**Ready for Implementation**: Yes  
**Next Step**: Begin Task 1.1 (Timing Instrumentation Helper)
