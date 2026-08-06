# Graph Report - D:\GIT VPOINT\2026-WACS  (2026-08-04)

## Corpus Check
- 412 files · ~352,680 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 6957 nodes · 20402 edges · 304 communities (277 shown, 27 thin omitted)
- Extraction: 87% EXTRACTED · 13% INFERRED · 0% AMBIGUOUS · INFERRED: 2746 edges (avg confidence: 0.73)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Code Editor Module
- Rich Editor Module
- Chart Module
- Rich Editor Module
- Code Editor Module
- Code Editor Module
- Chart Module
- Chart Module
- Code Editor Module
- Code Editor Module
- Markdown Editor Module
- Chart Module
- Rich Editor Module
- Chart Module
- Hak Akses Resource Module
- Code Editor Module
- Date Range Component Module
- Chart Module
- Inbox Whatsapp Module
- Markdown Editor Module
- Kategori Ticket Resource Module
- Pengguna Resource Module
- Tables Module
- Rich Editor Module
- Anggota Grup Whatsapp Module
- Chart Module
- Chart Module
- Chart Module
- Support Module
- Chart Module
- Chart Module
- Markdown Editor Module
- Notifications Module
- Code Editor Module
- Chart Module
- Markdown Editor Module
- Code Editor Module
- Rich Editor Module
- Chart Module
- File Upload Module
- External Auth Service Module
- Task Module
- Select Module
- Chart Module
- Register Module
- Markdown Editor Module
- Chart Module
- Slider Module
- Spec Module
- Chart Module
- Chart Module
- Chart Module
- Code Editor Module
- Rich Editor Module
- Chart Module
- Date Range Component Module
- Date Range Component Module
- Ai Auto Reply Service Module
- Rich Editor Module
- Select Module
- Chart Module
- Draft Pengetahuan Resource Module
- Vpoint Assistant Module
- Select Module
- Select Module
- Ai Agent Module
- Markdown Editor Module
- Rich Editor Module
- Support Module
- Access Permissions Module
- Chart Module
- Chart Module
- Process Ai Auto Reply Job Module
- Rich Editor Module
- Chart Module
- Chart Module
- Attachment Controller Module
- Waha Sender Module
- Support Module
- Dashboard Module
- Support Module
- Echo Module
- Rich Editor Module
- Support Module
- Chart Module
- Package Module
- Waha Webhook Processor Module
- Locale Formatter Module
- Markdown Editor Module
- Rich Editor Module
- Rich Editor Module
- Date Range Component Module
- Log Data Module
- App Module
- External Auth Controller Module
- Rich Editor Module
- Echo Module
- Select Module
- Chat Belum Terbalas Notifier Module
- Ai Knowledge Learning Service Module
- Task Resource Module
- Date Range Component Module
- Composer Module
- Anggota Grup Whatsapp Resource Module
- Customer Resource Module
- Grup Whatsapp Resource Module
- Nomor Whatsapp Resource Module
- Ticket Resource Module
- Select Module
- Select Module
- Job Schedule Resource Module
- Plan Task Dan Ticketing Module
- Bug Analisis Duplicate Tchat Module
- Waha Media Controller Module
- Composer Module
- Color Picker Module
- Select Module
- Chart Module
- Chart Module
- Date Range Component Module
- Design Module
- Requirements Module
- Tasks Module
- Select Module
- Select Module
- Select Module
- Readme Module
- Agents Module
- Proposal Module
- Proposal Module
- File Upload Module
- Select Module
- Plan Login Register Google Sso Module
- Readme Module
- Import Vtoken Customers To Instansi Module
- Proposal Module
- Proposal Module
- Inbox Whatsapp Blade Module
- Database Schema Wacs Module
- Waha Inbox Updated Module
- Proposal Module
- Rich Editor Module
- Support Module
- Date Range Component Module
- Echo Module
- Proposal Module
- Prioritas Ticket Resource Module
- Status Task Resource Module
- Status Ticket Resource Module
- Chat Initiation Service Module
- File Upload Module
- Schemas Module
- Spec Module
- Proposal Module
- Project Module
- Echo Module
- Echo Module
- Chart Module
- Seo Banner Module
- Pengguna User Provider Module
- Proposal Module
- Proposal Module
- Composer Module
- Ai Agent Img Id1 Module
- Ai Agent Img Id2 Module
- Logo Primary Module
- Composer Module
- Composer Module
- Composer Module
- Vpoint Assistant Blade Module
- Example Test Module
- Proposal Module
- Tasks Module
- Actions Module
- File Upload Module
- Actions Module
- Plan Ai Learning Dari Chat Customer Module
- Composer Module
- 2026 04 27 000001 Create Vpoint Care Schema Module
- Tasks Module
- Ai Agent Blade Module
- Plan Audit Ui Ux Ai Agent Light Outline Module
- Spec Module
- Proposal Module
- Composer Module
- Composer Module
- Ai Agent Module
- Ic Google Module
- Design Module
- Readme Module
- Spec Module
- Spec Module
- Spec Module
- Composer Module
- 0001 01 01 000000 Create Users Table Module
- 0001 01 01 000001 Create Cache Table Module
- 0001 01 01 000002 Create Jobs Table Module
- Plan Filament Full Width Breadcrumbs Module
- Proposal Module
- Proposal Module
- Proposal Module
- Proposal Module
- Dashboard Blade Module
- Ai Provider 9router 2 Module
- Ai Provider Deepseek 2 Module
- Ai Provider Openai 2 Module
- Ai Provider Openrouter 2 Module
- Checkbox List Module
- Key Value Module
- Tags Input Module
- Providers Module
- App Module

## God Nodes (most connected - your core abstractions)
1. `o()` - 227 edges
2. `r()` - 188 edges
3. `i()` - 169 edges
4. `t()` - 160 edges
5. `h()` - 141 edges
6. `update()` - 136 edges
7. `constructor()` - 134 edges
8. `u()` - 130 edges
9. `NavigationHelper` - 122 edges
10. `resolve()` - 93 edges

## Surprising Connections (you probably didn't know these)
- `Keamanan Operasional (secret handling, HTTPS, backup)` --semantically_similar_to--> `Aturan Teknis WACS`  [INFERRED] [semantically similar]
  README.md → AGENTS.md
- `Urutan fallback model: ModelInstructAi -> ModelAi -> config provider` --semantically_similar_to--> `Alur Pemilihan Model (Model Selection Flow)`  [INFERRED] [semantically similar]
  docs/PLAN_AI_INSTRUCT_MODEL.md → .kiro/specs/ai-model-instruct-and-ui-improvements/design.md
- `Simetri Translation Keys id/en` --semantically_similar_to--> `Aturan multilingual translation key id/en`  [INFERRED] [semantically similar]
  .kiro/specs/ai-model-instruct-and-ui-improvements/design.md → docs/PLAN_AI_INSTRUCT_MODEL.md
- `SchemaCache::hasColumn optimisation recommendation` --semantically_similar_to--> `Cache Schema::hasColumn`  [INFERRED] [semantically similar]
  .kiro/specs/ai-model-instruct-and-ui-improvements/design.md → docs/PLAN_SCALABILITY_OPTIMIZATION_CHATBOT.md
- `AI Agent (auto-reply dan draft lokal)` --shares_data_with--> `MPengaturanAi (tabel pengaturan AI)`  [INFERRED]
  README.md → .kiro/specs/ai-model-instruct-and-ui-improvements/requirements.md

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Pipeline pemilihan model AI (mode -> instruct/primary -> config)** — _kiro_specs_ai_model_instruct_and_ui_improvements_design_getassistantmodel, _kiro_specs_ai_model_instruct_and_ui_improvements_design_getinstructmodel, _kiro_specs_ai_model_instruct_and_ui_improvements_design_getprimarymodel, _kiro_specs_ai_model_instruct_and_ui_improvements_requirements_modelinstructai, _kiro_specs_ai_model_instruct_and_ui_improvements_requirements_modelai, docs_plan_ai_instruct_model_model_fallback_order [EXTRACTED 1.00]
- **Alur lookup/pembuatan sesi chat yang menyebabkan duplicate TChat** — docs_bug_analisis_duplicate_tchat_wahawebhookprocessor, docs_bug_analisis_duplicate_tchat_findorcreatechat, docs_bug_analisis_duplicate_tchat_chatinitiationservice, docs_bug_analisis_duplicate_tchat_chatlookupservice, docs_bug_analisis_duplicate_tchat_lid_resolution_failure, docs_bug_analisis_duplicate_tchat_race_condition_guard [EXTRACTED 1.00]
- **Stack kontrol akses dan navigasi Filament** — docs_plan_hak_akses_menu_filament_permission_model, docs_plan_hak_akses_menu_filament_permission_codes, docs_plan_mhakakses_sidebar_sort_mhakakses_structure, docs_plan_mhakakses_sidebar_sort_navigationhelper, docs_plan_mhakakses_sidebar_sort_accesspermissions_seeder, readme_mpengguna [INFERRED 0.95]
- **Duplicate Instruct-Model Change Proposals** — openspec_changes_add_ai_instruct_model_proposal_model_instruct, openspec_changes_add_model_instruct_proposal_model_instruct_column, openspec_changes_add_ai_instruct_model_proposal_assistantmodel, openspec_changes_add_model_instruct_tasks_getassistantmodel, openspec_changes_add_ai_instruct_model_proposal_internalchatbotservice [INFERRED 0.85]
- **Reviewed Knowledge Learning Pipeline** — openspec_changes_add_reviewed_ai_learning_proposal_createdraftfromchat, openspec_changes_add_reviewed_ai_learning_proposal_pii_sanitization, openspec_changes_add_reviewed_ai_learning_proposal_extraction_json_contract, openspec_changes_add_reviewed_ai_learning_proposal_taidraftpengetahuan, openspec_changes_add_reviewed_ai_learning_specs_vpoint_care_spec_knowledge_review_workflow, openspec_changes_add_reviewed_ai_learning_proposal_mpengetahuan, openspec_changes_add_reviewed_ai_learning_proposal_relevantknowledge [EXTRACTED 1.00]
- **Ticket and Task Assignment Flow** — openspec_changes_add_task_and_ticketing_module_tasks_ticketservice, openspec_changes_add_task_and_ticketing_module_tasks_taskservice, openspec_changes_add_task_and_ticketing_module_proposal_assignment_history, openspec_changes_add_task_and_ticketing_module_proposal_assignment_notification, openspec_changes_add_task_and_ticketing_module_proposal_task_permissions [EXTRACTED 1.00]
- **Alur Pemilihan Model Instruct pada Auto-Reply** — openspec_changes_ai_model_instruct_and_ui_improvements_proposal_model_instruct_ai, openspec_changes_fix_instruct_model_auto_reply_selection_proposal_isfirstreply_ordering_defect, openspec_changes_fix_instruct_model_auto_reply_selection_proposal_provider_flag_propagation_defect, openspec_changes_fix_instruct_model_auto_reply_selection_proposal_empty_string_fallback_defect, openspec_changes_fix_instruct_model_auto_reply_selection_specs_vpoint_care_spec_auto_reply_model_selection, openspec_changes_fix_instruct_model_auto_reply_selection_specs_vpoint_care_spec_audit_model_auto_reply_akurat, openspec_changes_fix_instruct_model_auto_reply_selection_specs_vpoint_care_spec_penanganan_model_instruct_kosong [INFERRED 0.85]
- **Sistem Desain Light Outline Global Filament Admin** — openspec_changes_audit_ai_agent_light_outline_ui_proposal_light_outline_theme, openspec_changes_audit_ai_agent_light_outline_ui_proposal_no_shadow_no_gradient, openspec_changes_audit_ai_agent_light_outline_ui_proposal_monospace_technical_textarea, openspec_changes_audit_ai_agent_light_outline_ui_proposal_compact_typography_scale, openspec_changes_audit_ai_agent_light_outline_ui_specs_vpoint_care_spec_global_filament_admin_light_outline_system, openspec_changes_audit_ai_agent_light_outline_ui_specs_vpoint_care_spec_global_filament_admin_typography_scale, openspec_changes_audit_ai_agent_light_outline_ui_specs_vpoint_care_spec_global_technical_textarea_monospace, openspec_changes_audit_ai_agent_light_outline_ui_specs_vpoint_care_spec_global_component_consistency [EXTRACTED 1.00]
- **Pipeline Async Webhook → AI Reply → Debounced Broadcast** — res_waha_docker_compose_whatsapp_hook, openspec_changes_scalability_optimization_and_chatbot_specs_vpoint_care_spec_asynchronous_webhook_processing, openspec_changes_scalability_optimization_and_chatbot_specs_vpoint_care_spec_dedicated_queue_workers, openspec_changes_scalability_optimization_and_chatbot_specs_vpoint_care_spec_queue_ai_deduplication, openspec_changes_scalability_optimization_and_chatbot_specs_vpoint_care_spec_debounced_broadcast, src_docker_compose_queue_webhooks, src_docker_compose_queue_ai, src_docker_compose_queue_broadcasts, src_docker_compose_reverb, src_docker_compose_redis [INFERRED 0.85]
- **AI provider icon set for AI Agent provider selection** — src_public_images_ai_provider_openai_2_ai_provider_openai, src_public_images_ai_provider_deepseek_2_ai_provider_deepseek, src_public_images_ai_provider_openrouter_2_ai_provider_openrouter, src_public_images_ai_provider_9router_2_ai_provider_9router [INFERRED 0.95]
- **VPoint brand mark asset family (light/dark variants duplicated across res, src/res and src/public/images)** — src_public_images_logo_primary_logo_primary, src_public_images_logo_secondary_logo_secondary, src_res_logo_primary_logo_primary, src_res_logo_secondary_logo_secondary, res_logo_1_logo_1, res_logo_2_logo_2 [EXTRACTED 1.00]
- **Robot mascot eye composite: paired bitmaps clipped into the AI-Agent.svg face** — res_ai_agent_images_ai_agent_imgid1_eye_glow_bitmap, res_ai_agent_images_ai_agent_imgid1_companion_imgid2, res_ai_agent_images_ai_agent_imgid1_clip_path_placement, res_ai_agent_images_ai_agent_imgid1_svg_host_illustration [INFERRED 0.85]
- **Paired glow rasters placed side by side at y=5222 form the mascot's eyes in AI-Agent.svg** — res_ai_agent_images_ai_agent_imgid2_eye_glow_bitmap, res_ai_agent_images_ai_agent_imgid2_companion_imgid1, res_ai_agent_images_ai_agent_imgid2_svg_placement_right_eye, res_ai_agent_images_ai_agent_imgid2_svg_host_illustration [EXTRACTED 1.00]
- **VPoint Care social/SEO preview identity: brand mark, tagline and flat CS illustration composed into the Open Graph banner served by the SEO meta component** — src_public_seo_banner_jpg, src_public_seo_banner_brand_vpoint_care, src_public_seo_banner_tagline_layanan_cs_digital, src_public_seo_banner_omnichannel_cs_motif, src_resources_views_components_seo_meta_component, src_public_seo_banner_open_graph_preview [INFERRED 0.85]

## Communities (304 total, 27 thin omitted)

### Community 0 - "Code Editor Module"
Cohesion: 0.01
Nodes (197): aa(), addChanges(), addSelection(), after(), Ag(), ak(), Ao(), applyTransaction() (+189 more)

### Community 1 - "Rich Editor Module"
Cohesion: 0.03
Nodes (281): ac(), bX(), compare(), fromJSON(), map(), mS(), resolve(), slot() (+273 more)

### Community 2 - "Chart Module"
Cohesion: 0.01
Nodes (119): Ac(), addControllers(), addElements(), addPlugins(), addScales(), af(), bd(), beforeDraw() (+111 more)

### Community 3 - "Rich Editor Module"
Cohesion: 0.01
Nodes (138): [g](), ab(), accepts(), addExtensions(), addGlobalAttributes(), addHackNode(), addTextblockHacks(), an() (+130 more)

### Community 4 - "Code Editor Module"
Cohesion: 0.02
Nodes (165): accept(), active(), add(), addChunk(), addEventListener(), addInfoPane(), addInner(), addWindowListeners() (+157 more)

### Community 5 - "Code Editor Module"
Cohesion: 0.02
Nodes (111): Bc(), addControllers(), addPlugins(), addScales(), afterDraw(), an(), as(), bl() (+103 more)

### Community 6 - "Chart Module"
Cohesion: 0.03
Nodes (128): ad(), addToSet(), applyChanges(), balanced(), baseIndent(), baseIndentFor(), blockAt(), bn() (+120 more)

### Community 7 - "Chart Module"
Cohesion: 0.03
Nodes (112): addBlockWidget(), addBreak(), addComposition(), addDelimiter(), addInlineWidget(), addLine(), addLineStart(), addLineStartIfNotCovered() (+104 more)

### Community 8 - "Code Editor Module"
Cohesion: 0.06
Nodes (107): ac(), Ad(), after(), am(), ap(), Ax(), Ba(), before() (+99 more)

### Community 9 - "Code Editor Module"
Cohesion: 0.03
Nodes (104): _0(), addActions(), addChild(), addElement(), addGaps(), addLeafElement(), addNode(), advance() (+96 more)

### Community 10 - "Markdown Editor Module"
Cohesion: 0.04
Nodes (94): abutsStart(), after(), am(), as(), before(), bm(), _cachedScopes(), Cm() (+86 more)

### Community 11 - "Chart Module"
Cohesion: 0.04
Nodes (94): acquireContext(), adjustHitBoxes(), afterDraw(), Ao(), bs(), bt(), calculateLabelRotation(), _calculatePadding() (+86 more)

### Community 12 - "Rich Editor Module"
Cohesion: 0.04
Nodes (92): addCompletion(), addCompletions(), addNamespace(), addNamespaceObject(), ah(), AP(), atLastNode(), c$() (+84 more)

### Community 13 - "Chart Module"
Cohesion: 0.03
Nodes (22): Filament\Tables\Concerns\InteractsWithTable, Filament\Tables\Contracts\HasTable, Illuminate\Database\Eloquent\Builder, HistoriChat, BackedEnum, Htmlable, BackedEnum, MasterCustomer (+14 more)

### Community 14 - "Hak Akses Resource Module"
Cohesion: 0.04
Nodes (87): readOnly(), Aa(), active(), ad(), addBox(), addEventListener(), afterBuildTicks(), afterCalculateLabelRotation() (+79 more)

### Community 15 - "Code Editor Module"
Cohesion: 0.04
Nodes (85): ah(), applyStack(), Ar(), At(), _calculateBarValuePixels(), calculateCircumference(), cf(), _circumference() (+77 more)

### Community 16 - "Date Range Component Module"
Cohesion: 0.03
Nodes (34): _abortUpdateCursor(), alignCursorFriendly(), _allocateBlock(), _applyHistoryState(), Be(), cursorPos(), _delayUpdateCursor(), ee() (+26 more)

### Community 17 - "Chart Module"
Cohesion: 0.05
Nodes (73): ad(), af(), ai(), ao(), Ba(), Cc(), cd(), dd() (+65 more)

### Community 18 - "Inbox Whatsapp Module"
Cohesion: 0.06
Nodes (6): Filament\Forms\Concerns\InteractsWithForms, Filament\Forms\Contracts\HasForms, Livewire\Features\SupportFileUploads\TemporaryUploadedFile, Livewire\WithFileUploads, InboxWhatsapp, Htmlable

### Community 19 - "Markdown Editor Module"
Cohesion: 0.11
Nodes (15): App\Filament\Concerns\HasMenuBreadcrumbs, BackedEnum, Filament\Notifications\Notification, Filament\Pages\Page, Filament\Resources\Pages\ManageRecords, Filament\Resources\Resource, Filament\Schemas\Schema, Filament\Tables\Table (+7 more)

### Community 20 - "Kategori Ticket Resource Module"
Cohesion: 0.04
Nodes (10): Action, HariLiburResource, ManageHariLiburs, InstansiResource, ManageInstansis, ManagePengetahuans, PengetahuanResource, ManagePenggunas (+2 more)

### Community 21 - "Pengguna Resource Module"
Cohesion: 0.09
Nodes (64): ae(), areRecordsSelected(), areRecordsToggleable(), be(), C(), canSelectAllRecords(), Ce(), D() (+56 more)

### Community 22 - "Tables Module"
Cohesion: 0.06
Nodes (69): nh(), adjustHitBoxes(), At(), bh(), bi(), bo(), calculateLabelRotation(), _calculatePadding() (+61 more)

### Community 23 - "Rich Editor Module"
Cohesion: 0.06
Nodes (23): App\Models\Concerns\UsesSqlServerUuid, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo, ChatbotMessage, ChatSession, JobSchedule, AnggotaGrupWhatsapp, Customer (+15 more)

### Community 24 - "Anggota Grup Whatsapp Module"
Cohesion: 0.05
Nodes (65): afterAutoSkip(), applyStack(), Ar(), buildLookupTable(), _calculateBarIndexPixels(), _calculateBarValuePixels(), _computeGridLineItems(), countVisibleElements() (+57 more)

### Community 25 - "Chart Module"
Cohesion: 0.04
Nodes (62): bt(), closest(), connectSelection(), deselectNode(), disconnectSelection(), domAfterPos(), domAtPos(), domFromPos() (+54 more)

### Community 26 - "Chart Module"
Cohesion: 0.05
Nodes (62): Em(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterFit(), afterSetDimensions(), afterTickToLabelConversion(), afterUpdate() (+54 more)

### Community 27 - "Chart Module"
Cohesion: 0.08
Nodes (60): _a(), Ae(), ar(), as(), bc(), ce(), ci(), cl() (+52 more)

### Community 28 - "Support Module"
Cohesion: 0.05
Nodes (60): addElements(), addEventListener(), al(), apply(), beforeUpdate(), bs(), Bt(), buildOrUpdateElements() (+52 more)

### Community 29 - "Chart Module"
Cohesion: 0.05
Nodes (59): bg(), bl(), cg(), clone(), create(), defaultWeekSettings(), dtFormatter(), eras() (+51 more)

### Community 30 - "Chart Module"
Cohesion: 0.06
Nodes (41): apply(), as(), At(), bo(), close(), closeQuietly(), co(), Ga() (+33 more)

### Community 31 - "Markdown Editor Module"
Cohesion: 0.16
Nodes (55): at(), Be(), Cr(), Ct(), de(), df(), dr(), dt() (+47 more)

### Community 32 - "Notifications Module"
Cohesion: 0.09
Nodes (51): append(), as(), canReplaceWith(), close(), closeFrontierNode(), compatibleContent(), computeWrapping(), contentMatchAt() (+43 more)

### Community 33 - "Code Editor Module"
Cohesion: 0.06
Nodes (31): actions(), button(), c(), close(), configureAnimations(), configureTransitions(), constructor(), danger() (+23 more)

### Community 34 - "Chart Module"
Cohesion: 0.05
Nodes (44): aS(), at(), b1(), be(), combine(), ensureLineGaps(), fi(), fromClass() (+36 more)

### Community 35 - "Markdown Editor Module"
Cohesion: 0.05
Nodes (9): Rd(), Bi(), Bn(), br(), ji(), Ri(), te(), Vi() (+1 more)

### Community 36 - "Code Editor Module"
Cohesion: 0.10
Nodes (46): Ah(), Bh(), Bi(), ch(), ct(), dh(), Eh(), Fi() (+38 more)

### Community 37 - "Rich Editor Module"
Cohesion: 0.07
Nodes (46): $a(), an(), au(), buildOrUpdateControllers(), buildOrUpdateElements(), Ca(), _calculateBarIndexPixels(), Cn() (+38 more)

### Community 38 - "Chart Module"
Cohesion: 0.06
Nodes (45): activeForPoint(), addActive(), addBlock(), addLineDeco(), Ar(), blankContent(), boundChange(), chunkEnd() (+37 more)

### Community 39 - "File Upload Module"
Cohesion: 0.07
Nodes (31): Ap(), bi(), c(), clickPercent(), dm(), e(), em(), Fe() (+23 more)

### Community 40 - "External Auth Service Module"
Cohesion: 0.09
Nodes (8): Attribute, Filament\Models\Contracts\HasAvatar, Illuminate\Database\Eloquent\Casts\Attribute, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, PenggunaExternalIdentity, Pengguna, ExternalAuthService

### Community 41 - "Task Module"
Cohesion: 0.07
Nodes (12): Carbon\CarbonInterface, Illuminate\Database\Eloquent\Relations\HasMany, PHPUnit\Framework\TestCase, Instansi, self, Task, self, Ticket (+4 more)

### Community 42 - "Select Module"
Cohesion: 0.06
Nodes (44): acceptToken(), allows(), an(), AQ(), bd(), Cg(), clearDelayedAndroidKey(), $d() (+36 more)

### Community 43 - "Chart Module"
Cohesion: 0.08
Nodes (34): applyDisabledState(), b(), be(), Cn(), D(), disable(), Dn(), _e() (+26 more)

### Community 44 - "Register Module"
Cohesion: 0.19
Nodes (35): W(), b(), $c(), ca(), D(), E(), g(), He() (+27 more)

### Community 45 - "Markdown Editor Module"
Cohesion: 0.07
Nodes (43): addAll(), addDOM(), addElement(), addElementByRule(), addToSet(), allowedMarks(), allowsMarkType(), apply() (+35 more)

### Community 46 - "Chart Module"
Cohesion: 0.10
Nodes (36): Ae(), ar(), Be(), Bt(), De(), _e(), Ee(), er() (+28 more)

### Community 47 - "Slider Module"
Cohesion: 0.06
Nodes (42): alpha(), aspectRatio(), Bo(), ch(), cs(), dh(), Ec(), fi() (+34 more)

### Community 48 - "Spec Module"
Cohesion: 0.07
Nodes (42): _a(), aa(), add(), alpha(), ba(), br(), ca(), Ce() (+34 more)

### Community 49 - "Chart Module"
Cohesion: 0.05
Nodes (41): ModelInstructAi Column & Routing, property_exists() Guard pada getInstructModel(), Satu API Call untuk Suggested Replies, Cacat 3: Fallback ?? Meloloskan String Kosong, Cacat 2: Flag Tidak Diteruskan ke Provider Chat-Completions, Resolusi Konflik Spec Pemilihan Model Auto-Reply, Requirement: Auto-Reply Model Selection (MODIFIED), Requirement: Penanganan Model Instruct Kosong (+33 more)

### Community 50 - "Chart Module"
Cohesion: 0.07
Nodes (39): charCategorizer(), cs(), di(), f1(), flatten(), getChild(), getCursor(), getDeco() (+31 more)

### Community 51 - "Chart Module"
Cohesion: 0.10
Nodes (38): al(), An(), bo(), Bt(), co(), Dn(), ef(), En() (+30 more)

### Community 52 - "Code Editor Module"
Cohesion: 0.08
Nodes (37): add(), Bf(), _computeLabelSizes(), createResolver(), daysInYear(), dd(), de(), fs() (+29 more)

### Community 53 - "Rich Editor Module"
Cohesion: 0.15
Nodes (37): at(), Bn(), bt(), cs(), Ds(), et(), Fe(), fs() (+29 more)

### Community 54 - "Chart Module"
Cohesion: 0.12
Nodes (3): Collection, AiAutoReplyService, Carbon

### Community 55 - "Date Range Component Module"
Cohesion: 0.10
Nodes (28): b(), be(), Cn(), D(), Dn(), en(), ft(), gn() (+20 more)

### Community 56 - "Date Range Component Module"
Cohesion: 0.08
Nodes (36): average(), beforeDatasetsDraw(), bu(), dataset(), ee(), En(), Fd(), fe() (+28 more)

### Community 57 - "Ai Auto Reply Service Module"
Cohesion: 0.14
Nodes (36): a(), aggregate(), append(), _appendChar(), _appendCharRaw(), _appendEager(), _appendPlaceholder(), appendTail() (+28 more)

### Community 58 - "Rich Editor Module"
Cohesion: 0.06
Nodes (6): getBreadcrumbs(), DraftPengetahuanResource, Htmlable, ManageDraftPengetahuans, DraftPengetahuan, FilamentBreadcrumbs

### Community 59 - "Select Module"
Cohesion: 0.10
Nodes (3): BackedEnum, VPointAssistant, InternalChatbotService

### Community 60 - "Chart Module"
Cohesion: 0.16
Nodes (35): ai(), bn(), ci(), ct(), di(), Dt(), Et(), gi() (+27 more)

### Community 61 - "Draft Pengetahuan Resource Module"
Cohesion: 0.16
Nodes (35): ai(), bn(), ci(), ct(), di(), Dt(), Et(), gi() (+27 more)

### Community 62 - "Vpoint Assistant Module"
Cohesion: 0.08
Nodes (35): afterDatasetsUpdate(), Ao(), beforeDatasetDraw(), beforeDatasetsDraw(), beforeDraw(), buildOrUpdateControllers(), co(), _createItems() (+27 more)

### Community 63 - "Select Module"
Cohesion: 0.08
Nodes (34): ai(), Ba(), cd(), contains(), _d(), describe(), ds(), E() (+26 more)

### Community 64 - "Select Module"
Cohesion: 0.11
Nodes (4): AiAgent, BackedEnum, Htmlable, AiSettings

### Community 65 - "Ai Agent Module"
Cohesion: 0.14
Nodes (32): ae(), B(), cr(), de(), dt(), Ee(), fr(), g() (+24 more)

### Community 66 - "Markdown Editor Module"
Cohesion: 0.10
Nodes (4): Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder, AccessPermissions, DatabaseSeeder

### Community 67 - "Rich Editor Module"
Cohesion: 0.08
Nodes (31): Tl(), cc(), dc(), defaultType(), done(), eat(), edge(), emptyChildAt() (+23 more)

### Community 68 - "Support Module"
Cohesion: 0.11
Nodes (31): buildOrUpdateScales(), ch(), D(), diff(), endOf(), ensureScalesHaveIDs(), Fn(), format() (+23 more)

### Community 69 - "Access Permissions Module"
Cohesion: 0.15
Nodes (11): Illuminate\Bus\Queueable, Illuminate\Contracts\Queue\ShouldQueue, Illuminate\Foundation\Bus\Dispatchable, Illuminate\Notifications\Notification, Illuminate\Queue\InteractsWithQueue, Illuminate\Queue\SerializesModels, ProcessAiAutoReplyJob, ProcessWebhookJob (+3 more)

### Community 70 - "Chart Module"
Cohesion: 0.09
Nodes (30): addMaps(), addNodeMark(), addStep(), addTransform(), appendMap(), appendMapping(), appendMappingInverted(), compress() (+22 more)

### Community 71 - "Chart Module"
Cohesion: 0.09
Nodes (30): afterAutoSkip(), bi(), buildLookupTable(), buildTicks(), eg(), fl(), _generate(), getDataTimestamps() (+22 more)

### Community 72 - "Process Ai Auto Reply Job Module"
Cohesion: 0.12
Nodes (11): Closure, Illuminate\Http\JsonResponse, Illuminate\Http\Request, Controller, LocaleController, PublicStorageController, AttachmentController, WahaWebhookController (+3 more)

### Community 74 - "Chart Module"
Cohesion: 0.19
Nodes (29): _a(), aa(), ba(), br(), Bt(), ct(), ei(), Fa() (+21 more)

### Community 75 - "Chart Module"
Cohesion: 0.10
Nodes (29): buildTicks(), calculateCircumference(), _circumference(), _computeAngle(), _computeLabelItems(), _computeLabelSizes(), computeTickLimit(), Do() (+21 more)

### Community 76 - "Attachment Controller Module"
Cohesion: 0.14
Nodes (28): alignCursor(), _blockStartPos(), c(), constructor(), doCommit(), e(), extractInput(), extractTail() (+20 more)

### Community 77 - "Waha Sender Module"
Cohesion: 0.14
Nodes (7): Filament\Pages\Dashboard, Filament\Pages\Dashboard\Concerns\HasFiltersForm, Illuminate\Support\Carbon, Dashboard, BackedEnum, Carbon, Htmlable

### Community 78 - "Support Module"
Cohesion: 0.15
Nodes (28): ai(), c(), destroy(), Do(), es(), f(), fo(), Ha() (+20 more)

### Community 79 - "Dashboard Module"
Cohesion: 0.11
Nodes (28): ae(), cc(), El(), first(), ga(), gh(), Ho(), ka() (+20 more)

### Community 80 - "Support Module"
Cohesion: 0.10
Nodes (12): ar(), cr(), Dt(), ir(), Me(), nr(), qt(), rr() (+4 more)

### Community 81 - "Echo Module"
Cohesion: 0.11
Nodes (27): afterDatasetsUpdate(), Da(), generateLabels(), getDatasetMeta(), getDataVisibility(), _getLegendItemAt(), getMaxBorderWidth(), _getSortedDatasetMetas() (+19 more)

### Community 82 - "Rich Editor Module"
Cohesion: 0.11
Nodes (26): addNode(), cg(), destroy(), destroyBetween(), destroyPluginViews(), destroyRest(), dg(), findIndexWithChild() (+18 more)

### Community 83 - "Support Module"
Cohesion: 0.15
Nodes (26): ca(), Dn(), En(), fn(), Gi(), ia(), Ii(), jt() (+18 more)

### Community 84 - "Chart Module"
Cohesion: 0.11
Nodes (26): average(), ci(), dataset(), getCenterPoint(), getPadding(), getProps(), hasValue(), Hi() (+18 more)

### Community 85 - "Package Module"
Cohesion: 0.08
Nodes (24): concurrently, laravel-echo, laravel-vite-plugin, pusher-js, dependencies, sweetalert2, devDependencies, concurrently (+16 more)

### Community 86 - "Waha Webhook Processor Module"
Cohesion: 0.16
Nodes (3): DateTimeInterface, WahaWebhookProcessor, SchemaCache

### Community 87 - "Locale Formatter Module"
Cohesion: 0.15
Nodes (10): Filament\Auth\Http\Responses\Contracts\LogoutResponse, Filament\Auth\Http\Responses\Contracts\RegistrationResponse, Illuminate\Http\RedirectResponse, Illuminate\Routing\Controller, Illuminate\Support\ServiceProvider, Livewire\Features\SupportRedirects\Redirector, ExternalAuthController, LandingLogoutResponse (+2 more)

### Community 89 - "Rich Editor Module"
Cohesion: 0.13
Nodes (24): Pr(), Ac(), bf(), bl(), Dc(), Do(), el(), Ha() (+16 more)

### Community 90 - "Rich Editor Module"
Cohesion: 0.11
Nodes (24): al(), getMinDaysInFirstWeek(), getMinimumDaysInFirstWeek(), getStartOfWeek(), getWeekendDays(), getWeekendWeekdays(), getWeekSettings(), gg() (+16 more)

### Community 91 - "Date Range Component Module"
Cohesion: 0.15
Nodes (22): Aa(), cf(), da(), fa(), ga(), Gr(), Jc(), Kr() (+14 more)

### Community 92 - "Log Data Module"
Cohesion: 0.10
Nodes (23): apply(), Cc(), chartOptionScopes(), ci(), constructor(), data(), Fa(), getDevicePixelRatio() (+15 more)

### Community 93 - "App Module"
Cohesion: 0.13
Nodes (9): Filament\Auth\Http\Responses\Contracts\LoginResponse, Filament\Auth\Pages\Login, Filament\Auth\Pages\Register, Filament\Models\Contracts\FilamentUser, Illuminate\Contracts\Auth\Authenticatable, Illuminate\Contracts\Support\Htmlable, LoginResponse, Login (+1 more)

### Community 94 - "External Auth Controller Module"
Cohesion: 0.16
Nodes (3): LogData, BackedEnum, Htmlable

### Community 95 - "Rich Editor Module"
Cohesion: 0.13
Nodes (11): close(), E(), G(), init(), P(), Q(), setUpResizeObserver(), X() (+3 more)

### Community 96 - "Echo Module"
Cohesion: 0.13
Nodes (21): allowsMarks(), bc(), checkContent(), es(), ic(), Ih(), im(), Jn() (+13 more)

### Community 97 - "Select Module"
Cohesion: 0.14
Nodes (20): Ce(), De(), ei(), Fe(), He(), Ht(), Ie(), ii() (+12 more)

### Community 98 - "Chat Belum Terbalas Notifier Module"
Cohesion: 0.19
Nodes (19): Ae(), bi(), Bt(), Ce(), De(), ei(), fn(), ht() (+11 more)

### Community 99 - "Ai Knowledge Learning Service Module"
Cohesion: 0.18
Nodes (5): Illuminate\Console\Command, ImportInstansiVToken, KirimNotifikasiChatBelumTerbalas, ChatBelumTerbalasNotifier, Carbon

### Community 100 - "Task Resource Module"
Cohesion: 0.17
Nodes (3): Htmlable, ViewChatSession, AiKnowledgeLearningService

### Community 101 - "Date Range Component Module"
Cohesion: 0.12
Nodes (3): ManageTasks, BackedEnum, TaskResource

### Community 102 - "Composer Module"
Cohesion: 0.18
Nodes (18): Ae(), as(), Ce(), Cn(), dt(), es(), fn(), Gt() (+10 more)

### Community 103 - "Anggota Grup Whatsapp Resource Module"
Cohesion: 0.15
Nodes (5): Filament\Actions\Action, Filament\Panel, Filament\PanelProvider, EditOwnProfileAction, AdminPanelProvider

### Community 104 - "Customer Resource Module"
Cohesion: 0.13
Nodes (17): composer install, Illuminate\\Foundation\\ComposerScripts::prePackageUninstall, npm install --ignore-scripts, npm run build, @php artisan config:clear --ansi, @php artisan key:generate, @php artisan migrate --force, @php artisan test (+9 more)

### Community 105 - "Grup Whatsapp Resource Module"
Cohesion: 0.12
Nodes (3): AnggotaGrupWhatsappResource, Htmlable, ManageAnggotaGrupWhatsapps

### Community 106 - "Nomor Whatsapp Resource Module"
Cohesion: 0.12
Nodes (3): CustomerResource, Htmlable, ManageCustomers

### Community 107 - "Ticket Resource Module"
Cohesion: 0.12
Nodes (3): GrupWhatsappResource, Htmlable, ManageGrupWhatsapps

### Community 108 - "Select Module"
Cohesion: 0.12
Nodes (3): NomorWhatsappResource, Htmlable, ManageNomorWhatsapps

### Community 109 - "Select Module"
Cohesion: 0.13
Nodes (3): ManageTickets, BackedEnum, TicketResource

### Community 110 - "Job Schedule Resource Module"
Cohesion: 0.13
Nodes (17): ak(), bd(), computeAttrs(), createChecked(), dd(), descAt(), endOfTextblock(), fc() (+9 more)

### Community 111 - "Plan Task Dan Ticketing Module"
Cohesion: 0.23
Nodes (17): Ae(), bi(), Bt(), Ce(), De(), ei(), fn(), ht() (+9 more)

### Community 112 - "Bug Analisis Duplicate Tchat Module"
Cohesion: 0.23
Nodes (17): applyDisabledState(), closeDropdown(), constructor(), destroy(), disable(), enable(), focusNextOption(), focusPreviousOption() (+9 more)

### Community 114 - "Composer Module"
Cohesion: 0.14
Nodes (15): Dua jalur guard: menu/sidebar dan URL/action langsung, Permission Code (dashboard.view, inbox.reply, ticket.manage, ...), Model Hak Akses Filament (MPeran/MHakAkses/MPeranHakAkses), AccessPermissions sebagai daftar permission bilingual resmi, Struktur Final MHakAkses (self-reference, SortOrder, IconString), NavigationHelper, Riwayat penugasan + notifikasi database (TTicketDPenugasan/TTaskDPenugasan), Lampiran privat maks 3 MB (disk attachments, download auth-gated) (+7 more)

### Community 115 - "Color Picker Module"
Cohesion: 0.15
Nodes (14): Minimalisme Implementasi, ChatInitiationService, ChatLookupService (usulan shared lookup), Bug Duplicate TChat, findOrCreateChat(), Race condition guard (cache lock / filtered unique index), TChat (header sesi chat), Reuse sesi aktif alih-alih membuat TChat baru (+6 more)

### Community 117 - "Chart Module"
Cohesion: 0.14
Nodes (13): framework, laravel, description, extra, laravel, keywords, dont-discover, license (+5 more)

### Community 118 - "Chart Module"
Cohesion: 0.15
Nodes (3): style(), update(), [x]()

### Community 119 - "Date Range Component Module"
Cohesion: 0.30
Nodes (14): closeDropdown(), constructor(), destroy(), focusNextOption(), focusPreviousOption(), getVisibleOptions(), handleDropdownKeydown(), handleSelectButtonKeydown() (+6 more)

### Community 120 - "Design Module"
Cohesion: 0.20
Nodes (14): active(), _animateOptions(), cancel(), _createAnimations(), _createDescriptors(), _descriptors(), _notify(), _notifyStateChanges() (+6 more)

### Community 121 - "Requirements Module"
Cohesion: 0.21
Nodes (14): _adjustRangeWithSeparators(), bindBlock(), _findSeparatorAround(), nearestInputPos(), popState(), _pushLeft(), pushLeftBeforeFilled(), pushLeftBeforeInput() (+6 more)

### Community 122 - "Tasks Module"
Cohesion: 0.17
Nodes (13): Alpine.js auto-grow textarea pattern (x-on:input + x-effect), getAssistantModel (model dispatch by mode), getInstructModel (instruct model with fallback), getPrimaryModel (ModelAi then provider config default), Alur Pemilihan Model (Model Selection Flow), AiAgent (halaman pengaturan AI), Mode Cepat (fast), ModelAi / Model Utama (+5 more)

### Community 123 - "Select Module"
Cohesion: 0.17
Nodes (13): loadHistory() suggestedReplies reset fix, Suggested Replies Lifecycle, AiAutoReplyService, InternalChatbotService, Isolasi Model — AiAutoReplyService tidak berubah, Mode Ringan (light), ModelInstructAi (kolom nvarchar(100) NULL), Suggested Replies (+5 more)

### Community 124 - "Select Module"
Cohesion: 0.15
Nodes (13): 9Router Provider Option (Proposal), Encrypted Provider API Key Storage Pattern, KirimKeWaha / DraftLokal Send Mode, 9Router vs OpenRouter Identity Ambiguity, Requirement: 9Router AI Auto Reply, Requirement: 9Router Provider Option, generateChatCompletionReply() Provider Helper, MPengaturanAi.NineRouterApiKeyTerenkripsi Column (+5 more)

### Community 125 - "Select Module"
Cohesion: 0.37
Nodes (13): createOptionElement(), deferPositionDropdown(), filterOptions(), handleSearch(), hideLoadingState(), openDropdown(), populateLabelRepositoryFromOptions(), positionDropdown() (+5 more)

### Community 126 - "Readme Module"
Cohesion: 0.28
Nodes (13): addBadgesForSelectedOptions(), addSingleBadge(), addSingleSelectionDisplay(), createBadgeElement(), createRemoveButton(), getLabelForSingleSelection(), getLabelsForMultipleSelection(), getSelectedOptionLabel() (+5 more)

### Community 127 - "Agents Module"
Cohesion: 0.37
Nodes (13): createOptionElement(), deferPositionDropdown(), filterOptions(), handleSearch(), hideLoadingState(), openDropdown(), populateLabelRepositoryFromOptions(), positionDropdown() (+5 more)

### Community 128 - "Proposal Module"
Cohesion: 0.22
Nodes (13): acquireContext(), Dr(), Ee(), getContext(), getLineWidthForValue(), il(), kl(), oh() (+5 more)

### Community 129 - "Proposal Module"
Cohesion: 0.20
Nodes (12): Aturan Teknis WACS, Kegagalan resolve @lid ke nomor telepon, MPengetahuan (Knowledge Base AI), Vector search / embedding opsional (Fase 4), Refactor Auth ke MPengguna, Penghapusan tabel users dan App\Models\User, Provider 9Router pada AI Agent, Penamaan env NINEROUTER_* (hindari nama diawali angka) (+4 more)

### Community 130 - "File Upload Module"
Cohesion: 0.18
Nodes (12): Struktur openspec/changes/<change-slug>/, Definition of Done, Format Delta Spec (Requirement/Scenario GIVEN-WHEN-THEN), OpenSpec Sebagai Standar Perencanaan, Keputusan stack Laravel 13.x + PHP 8.3, Rencana Pembuatan Webapps VPoint Care, Deployment VPoint Care dengan Docker, Dockerfile PHP-FPM + ODBC msodbcsql18 + sqlsrv (+4 more)

### Community 131 - "Select Module"
Cohesion: 0.17
Nodes (12): Lightweight Keyword Retrieval Strategy, MPengetahuan Knowledge Base, Per-Chat Knowledge Mode (TChat.ModeKnowledgeAi), Prompt Budget Rule, relevantKnowledge() Weighted Retrieval Scoring, MPengetahuan SearchKeywords / PrioritasAi Retrieval Fields, Requirement: Auto Reply Uses Approved Knowledge Only, Requirement: Lightweight Knowledge Retrieval (+4 more)

### Community 132 - "Plan Login Register Google Sso Module"
Cohesion: 0.18
Nodes (12): Max-Height Textarea 200px, Bug Fix UI VPoint Assistant (shadow, textarea, suggested replies), Fix loadHistory() Suggested Replies, Auto-Grow Textarea PromptSistem, Light Outline Theme, Monospace untuk Textarea Teknis, Tanpa Shadow dan Gradient, Requirement: Global Component Consistency (+4 more)

### Community 133 - "Readme Module"
Cohesion: 0.23
Nodes (12): am(), be(), cm(), je(), oe(), pe(), Pl(), Rt() (+4 more)

### Community 134 - "Import Vtoken Customers To Instansi Module"
Cohesion: 0.30
Nodes (12): addSingleBadge(), addSingleSelectionDisplay(), createBadgeElement(), createRemoveButton(), getLabelForSingleSelection(), getLabelsForMultipleSelection(), getSelectedOptionLabel(), getSelectedOptionLabels() (+4 more)

### Community 135 - "Proposal Module"
Cohesion: 0.21
Nodes (12): ac(), cs(), Es(), lo(), ls(), nc(), path(), sc() (+4 more)

### Community 136 - "Proposal Module"
Cohesion: 0.18
Nodes (11): Database dan Data Safety, Filament Panel Builder sebagai admin panel, Konvensi database uniqueidentifier + NEWSEQUENTIALID + audit column, Whitelist domain email perusahaan, Login/Register via Google dan SSO OIDC, ExternalAuthService, Kebijakan pending approval untuk user eksternal baru, Kenapa arsitektur ini (WAHA, SQL Server, Filament, Queue+Reverb) (+3 more)

### Community 137 - "Inbox Whatsapp Blade Module"
Cohesion: 0.18
Nodes (11): WahaWebhookProcessor, FAB Buat Chat di panel Daftar Chat, Fitur Mulai Chat Terlebih Dahulu, Circuit breaker WahaSender, ProcessAiAutoReplyJob (AI reply asinkron), ProcessWebhookJob (webhook asinkron), Scalability Optimization (Fase A), Inbox WhatsApp (+3 more)

### Community 139 - "Waha Inbox Updated Module"
Cohesion: 0.18
Nodes (11): AiSettings::get() 5-Minute Settings Cache, assistantModel() Model Selection Helper, Instruct Model Fallback Rules, InternalChatbotService (VPoint Assistant Runtime), VPoint Assistant, Requirement: VPoint Assistant memakai Model Instruct, Requirement: Opsi jawaban ringan WhatsApp memakai Model Instruct, VPoint Assistant UI Bug Fixes (+3 more)

### Community 140 - "Proposal Module"
Cohesion: 0.18
Nodes (11): Pending-Approval Registration Decision, Requirement: Pending User Approval, Private Attachment Storage with 3 MB Limit, Reuse MPrioritasTicket/MKategoriTicket for Tasks, task.view / task.manage Permissions, TTask Standalone Task Module, TTicket Ticket Header Schema, Requirement: Task Management (+3 more)

### Community 141 - "Rich Editor Module"
Cohesion: 0.18
Nodes (10): openStartChatDialog, refreshMappingChat, refreshProfilWaha, removeAttachment, resetSapaanAi, saveInternalNote, selectChat(, simpanBalasanLokal (+2 more)

### Community 142 - "Support Module"
Cohesion: 0.31
Nodes (10): MNomorDokumen, MPeran, MPeranHakAkses, MStatusTask, notifications, TTask, TTaskDChecklist, TTaskDKomentar (+2 more)

### Community 143 - "Date Range Component Module"
Cohesion: 0.29
Nodes (5): Illuminate\Broadcasting\Channel, Illuminate\Broadcasting\InteractsWithSockets, Illuminate\Contracts\Broadcasting\ShouldBroadcastNow, Illuminate\Foundation\Events\Dispatchable, WahaInboxUpdated

### Community 144 - "Echo Module"
Cohesion: 0.20
Nodes (10): Requirement: Secure External Auth Defaults, OAuth state/nonce and Redirect Validation, AiKnowledgeLearningService, createDraftFromChat(), Knowledge Extraction JSON Contract, HashKonten Content Deduplication, PII and Sensitive Data Sanitization, Requirement: Sensitive Data Sanitization (+2 more)

### Community 145 - "Proposal Module"
Cohesion: 0.27
Nodes (10): endIndex(), getObj(), hasProtocol(), render(), startIndex(), toFormattedHref(), toFormattedObject(), toFormattedString() (+2 more)

### Community 146 - "Prioritas Ticket Resource Module"
Cohesion: 0.24
Nodes (10): ar(), Cn(), Da(), fe(), J(), ne(), Nn(), wn() (+2 more)

### Community 147 - "Status Task Resource Module"
Cohesion: 0.29
Nodes (7): logReverbStatus(), readReverbStatusLogs(), reverbInitialSync, reverbStatusMessages, setWahaWsOnline(), syncCurrentReverbState(), writeReverbStatusLog()

### Community 148 - "Status Ticket Resource Module"
Cohesion: 0.22
Nodes (9): Human-in-the-loop RAG, TAiDraftPengetahuan Draft Knowledge Table, Why Not Fine-Tuning Now, Requirement: Knowledge Review Workflow, Requirement: Reviewed AI Knowledge Learning, Draft Knowledge AI Filament Resource, DraftPengetahuan Model and StatusReview Constants, knowledge.view / knowledge.manage Permission Reuse (+1 more)

### Community 153 - "Proposal Module"
Cohesion: 0.25
Nodes (9): Bp(), Cp(), Dp(), lm(), Op(), sa(), Ye(), yl() (+1 more)

### Community 155 - "Echo Module"
Cohesion: 0.25
Nodes (8): Test Koneksi AI (Provider Connection Test), Modified Requirement: AI Agent Settings (9Router), Requirement: AI Connection Test Dialog, Model Utama (ModelAi Primary Model), Requirement: Auto-reply tetap memakai Model Utama, No-Regression Guard for Auto-Reply Model, Requirement: Auto-Reply uses Primary Model Only, Requirement: AI Agent Settings (Base Spec)

### Community 156 - "Echo Module"
Cohesion: 0.25
Nodes (8): Inbox WhatsApp First-Session Model Rule, Model Instruct (ModelInstructAi), Requirement: Jawaban pertama Inbox WhatsApp memakai Model Instruct, Requirement: Pengaturan AI menyediakan Model Instruct, Cost and Latency Rationale for Split Models, ModelInstructAi Column (add-model-instruct), Schema::hasColumn() Fail-Safe Guard, Requirement: Model Instruct Column

### Community 157 - "Chart Module"
Cohesion: 0.25
Nodes (8): Allowed Email Domain Whitelist, Requirement: Google Login, SLA Overdue Calculation (BatasSlaMenit), Requirement: Ticketing (Expanded Delta), Core Domains of VPoint Care, VPoint Care / WACS Project, Requirement: Admin Authentication, Requirement: Ticketing (Base Spec)

### Community 158 - "Seo Banner Module"
Cohesion: 0.25
Nodes (8): a(), at(), d(), f(), H(), ji(), L(), pt()

### Community 159 - "Pengguna User Provider Module"
Cohesion: 0.25
Nodes (7): Be(), di(), e(), g(), i(), Ut(), xr()

### Community 160 - "Proposal Module"
Cohesion: 0.32
Nodes (8): Ct(), extend(), extractPatternOptions(), Ie(), optionsIsChanged(), Q(), shift(), toString()

### Community 161 - "Proposal Module"
Cohesion: 0.46
Nodes (8): VPoint Care Brand Identity (V-mark logo, dark navy wordmark), VPoint Care SEO Banner (JPG), Omnichannel Customer Service Motif (headset agent, chat bubble, email badge, phone caller), Open Graph / Social Sharing Preview Image (1200x630), VPoint Care SEO Banner (PNG), Tagline: Layanan Customer Service Digital yang Cepat, Mudah, dan Terintegrasi, Visual Style: Flat Vector Illustration, Dark Navy Ground with Coral/Teal/Amber Accents, SEO Meta Blade Component

### Community 163 - "Ai Agent Img Id1 Module"
Cohesion: 0.29
Nodes (7): Daftar Kanonik MStatusTicket/MKategoriTicket/MPrioritasTicket, DatabaseSeeder sebagai Sumber Tunggal Seed Master Ticketing, Keputusan SLA RENDAH = 2880 Menit, Requirement: Kompatibilitas Konsumen Master Ticketing, Requirement: Nilai SLA Prioritas Deterministik, Requirement: Sumber Tunggal Master Ticketing, TicketingMasterSeedTest

### Community 164 - "Ai Agent Img Id2 Module"
Cohesion: 0.29
Nodes (7): Kompatibilitas Dua Kunci Array (DibuatOlehNama & NamaPembuat), Trait ResolvesCatatanInternal, Resolusi Nama Pembuat via Satu Query (Anti N+1), Requirement: Atribusi Pembuat Catatan Internal, CatatanInternalTest, Shared WahaChatHelper, Requirement: Shared WahaChatHelper

### Community 165 - "Logo Primary Module"
Cohesion: 0.29
Nodes (7): Composer\\Config::disableProcessTimeout, npx concurrently -c \"#93c5fd,#c4b5fd,#86efac\" \"php artisan serve\" \"php artisan queue:listen --tries=1 --timeout=0\" \"php artisan reverb:start\" --names=server,queue,reverb --kill-others, npx concurrently -c \"#93c5fd,#c4b5fd,#fdba74,#86efac\" \"php artisan serve:vpoint\" \"php artisan queue:listen --tries=1 --timeout=0\" \"npm run dev\" \"php artisan reverb:start\" --names=server,queue,vite,reverb --kill-others, npx concurrently -c \"#c4b5fd,#86efac\" \"php artisan queue:listen --tries=1 --timeout=0\" \"php artisan reverb:start\" --names=queue,reverb --kill-others, dev, laragon, start

### Community 166 - "Composer Module"
Cohesion: 0.38
Nodes (7): AI Agent Module Visual Branding (VPoint Care WACS), Circular Clip-Path Placement at x=2970 y=5222 (502x509), Companion Asset AI-Agent_ImgID2.png (mirrored eye), AI Agent Mascot Eye Glow Bitmap (ImgID1), Radial Blue-on-Dark Gradient Motif, Referenced by PLAN_TAMBAH_9ROUTER_AI_AGENT.md asset list, AI-Agent.svg Robot Mascot Illustration (host artwork)

### Community 167 - "Composer Module"
Cohesion: 0.38
Nodes (7): AI Agent Module Visual Branding (VPoint Care WACS), Companion Asset AI-Agent_ImgID1.png (paired left eye), AI Agent Mascot Eye Glow Bitmap (ImgID2, 76x76 PNG), Radial Blue-on-Dark Glow Gradient Motif, AI-Agent.svg Robot Mascot Illustration (host artwork), SVG Raster Placement x=3616 y=5222 (509x509) - right eye slot, Low-Res Raster Upscaled ~6.7x Inside Vector Artwork (quality tradeoff)

### Community 168 - "Composer Module"
Cohesion: 0.38
Nodes (7): logo_1.svg — VPoint chevron brand mark, all-white (dark-background) variant, logo_2.svg — VPoint chevron brand mark, full-colour (blue #0D89E5 / red #E04040) variant, logo_primary.svg — primary (light-mode) VPoint Care brand mark and favicon, VPoint Care brand mark (concept), logo_secondary.svg — secondary (dark-mode) all-white VPoint Care brand mark, src/res/logo_primary.svg — source-of-truth copy of the light-mode brand mark, src/res/logo_secondary.svg — source-of-truth copy of the dark-mode brand mark

### Community 169 - "Vpoint Assistant Blade Module"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 170 - "Example Test Module"
Cohesion: 0.29
Nodes (7): require, filament/filament, laravel/framework, laravel/reverb, laravel/tinker, malzariey/filament-daterangepicker-filter, php

### Community 171 - "Proposal Module"
Cohesion: 0.29
Nodes (7): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 172 - "Tasks Module"
Cohesion: 0.33
Nodes (4): clearHistory, createKnowledgeDraft({{ $index }}), $set(, useSuggestedReply(@js($reply))

### Community 173 - "Actions Module"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 174 - "File Upload Module"
Cohesion: 0.40
Nodes (6): MPengaturanAi.ModelInstructAi Conditional Migration, Google and SSO External Login, MPengguna as Internal User Source of Truth, Idempotent SQL Server Migration Pattern, Development Rules (Route/SQL Server/Secret Constraints), Requirement: SQL Server Compatibility

### Community 175 - "Actions Module"
Cohesion: 0.40
Nodes (6): Assignment History (TTicketDPenugasan / TTaskDPenugasan), TicketAssignedNotification / TaskAssignedNotification, TaskNumberService, TaskService, TicketNumberService, TicketService

### Community 176 - "Plan Ai Learning Dari Chat Customer Module"
Cohesion: 0.73
Nodes (5): closeModal(), generateModalId(), init(), openModal(), syncActionModals()

### Community 177 - "Composer Module"
Cohesion: 0.33
Nodes (6): constructor(), define(), _getTestState(), getType(), registerListeners(), St()

### Community 179 - "Tasks Module"
Cohesion: 0.40
Nodes (5): AiKnowledgeLearningService, Human-in-the-loop RAG untuk AI Learning, Sanitasi PII sebelum ekstraksi knowledge, TAiDraftPengetahuan (tabel draft knowledge), AI sebagai asisten CS, bukan auto-reply langsung

### Community 180 - "Ai Agent Blade Module"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 181 - "Plan Audit Ui Ux Ai Agent Light Outline Module"
Cohesion: 0.60
Nodes (4): down(), splitSqlServerBatches(), tablesInDropOrder(), up()

### Community 182 - "Spec Module"
Cohesion: 0.67
Nodes (4): Design: AI Model Instruct & UI Improvements, Requirements: AI Model Instruct & UI Improvements, Implementation Plan: AI Model Instruct & UI Improvements, Task Dependency Graph (waves)

### Community 183 - "Proposal Module"
Cohesion: 0.50
Nodes (3): applyProviderPreset(, hapusApiKey, testKoneksiAi

### Community 184 - "Composer Module"
Cohesion: 0.67
Nodes (4): Filament Light Outline Design System (no shadow/gradient), Skala Tipografi Global Filament Admin, resources/css/filament/admin/theme.css (global design tokens), Perbaikan Breadcrumbs Filament via CSS

### Community 185 - "Composer Module"
Cohesion: 0.50
Nodes (4): Requirement: UI Model Instruct mendukung multilingual, Requirement: Model Instruct UI, primary_model / instruct_model Translation Keys, Requirement: Localization

### Community 186 - "Ai Agent Module"
Cohesion: 0.50
Nodes (4): Penonaktifan Aman Kode Master Non-Kanonik, Tabel Pemetaan Status/Kategori Lama ke Kanonik, Requirement: Penonaktifan Aman Master Non-Kanonik, Migration deactivate_non_canonical_ticketing_masters

### Community 187 - "Ic Google Module"
Cohesion: 0.50
Nodes (4): Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, @php artisan filament:upgrade, @php artisan package:discover --ansi, post-autoload-dump

### Community 188 - "Design Module"
Cohesion: 0.50
Nodes (4): @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\, post-create-project-cmd

### Community 189 - "Readme Module"
Cohesion: 0.50
Nodes (4): AI-Agent.svg — AI agent robot illustration (CorelDRAW artwork), AI.svg — headset AI bot face glyph (grey #4D4D4D), CS.svg — human customer-service agent illustration (CorelDRAW artwork), logo_ai.svg — AI auto-reply bot avatar served for AI-generated messages

### Community 190 - "Spec Module"
Cohesion: 0.50
Nodes (4): Google OAuth sign-in / sign-up option (concept), ic_google.svg — Google "G" four-colour auth provider icon, ic_sso.svg — blue "SSO" lettermark auth provider icon, Single sign-on (SSO) login option (concept)

### Community 191 - "Spec Module"
Cohesion: 0.67
Nodes (3): Simetri Translation Keys id/en, Bahasa Kerja (Bahasa Indonesia + istilah teknis source code), Aturan multilingual translation key id/en

### Community 192 - "Spec Module"
Cohesion: 0.67
Nodes (3): Redis untuk cache, queue, dan session, Integrasi VToken (import instansi/customer), Scheduler dan Queue (job_schedules, database queue)

### Community 193 - "Composer Module"
Cohesion: 0.67
Nodes (3): Requirement: External Identity Linking, Requirement: External User Registration, External Provider Identity Link Table

### Community 194 - "0001 01 01 000000 Create Users Table Module"
Cohesion: 0.67
Nodes (3): Optimasi Dashboard dengan SQL Aggregation, Requirement: Composite Database Indexes, Requirement: Optimasi Dashboard Query

### Community 195 - "0001 01 01 000001 Create Cache Table Module"
Cohesion: 0.67
Nodes (3): WAHA Webhook to Filament Architecture Flow, Requirement: WhatsApp Inbox, Requirement: WhatsApp Webhook Intake

### Community 196 - "0001 01 01 000002 Create Jobs Table Module"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

## Ambiguous Edges - Review These
- `HashKonten Content Deduplication` → `TCK/TSK Daily Sequence Number Format`  [AMBIGUOUS]
  openspec/changes/add-reviewed-ai-learning/proposal.md · relation: semantically_similar_to
- `Service app (vpointcare-php85)` → `robots.txt — Allow All Crawlers`  [AMBIGUOUS]
  src/public/robots.txt · relation: conceptually_related_to
- `Radial Blue-on-Dark Gradient Motif` → `AI Agent Module Visual Branding (VPoint Care WACS)`  [AMBIGUOUS]
  res/AI-Agent_Images/AI-Agent_ImgID1.png · relation: conceptually_related_to
- `Radial Blue-on-Dark Glow Gradient Motif` → `AI Agent Module Visual Branding (VPoint Care WACS)`  [AMBIGUOUS]
  res/AI-Agent_Images/AI-Agent_ImgID2.png · relation: conceptually_related_to
- `VPoint Care SEO Banner (PNG)` → `Open Graph / Social Sharing Preview Image (1200x630)`  [AMBIGUOUS]
  src/public/seo-banner.png · relation: conceptually_related_to

## Knowledge Gaps
- **176 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+171 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **27 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `HashKonten Content Deduplication` and `TCK/TSK Daily Sequence Number Format`?**
  _Edge tagged AMBIGUOUS (relation: semantically_similar_to) - confidence is low._
- **What is the exact relationship between `Service app (vpointcare-php85)` and `robots.txt — Allow All Crawlers`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **What is the exact relationship between `Radial Blue-on-Dark Gradient Motif` and `AI Agent Module Visual Branding (VPoint Care WACS)`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **What is the exact relationship between `Radial Blue-on-Dark Glow Gradient Motif` and `AI Agent Module Visual Branding (VPoint Care WACS)`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **What is the exact relationship between `VPoint Care SEO Banner (PNG)` and `Open Graph / Social Sharing Preview Image (1200x630)`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **Why does `o()` connect `Rich Editor Module` to `Code Editor Module`, `Chart Module`, `Rich Editor Module`, `Code Editor Module`, `Readme Module`, `Chart Module`, `Chart Module`, `Code Editor Module`, `Code Editor Module`, `Markdown Editor Module`, `Chart Module`, `Rich Editor Module`, `Code Editor Module`, `Hak Akses Resource Module`, `Code Editor Module`, `Proposal Module`, `Pengguna Resource Module`, `Tables Module`, `Chart Module`, `Support Module`, `Chart Module`, `Chart Module`, `Pengguna User Provider Module`, `Notifications Module`, `Chart Module`, `Code Editor Module`, `Rich Editor Module`, `Chart Module`, `File Upload Module`, `Select Module`, `Chart Module`, `Slider Module`, `Spec Module`, `Code Editor Module`, `Date Range Component Module`, `Date Range Component Module`, `Ai Auto Reply Service Module`, `Chart Module`, `Draft Pengetahuan Resource Module`, `Select Module`, `Rich Editor Module`, `Chart Module`, `Attachment Controller Module`, `Support Module`, `Dashboard Module`, `Support Module`, `Support Module`, `Chart Module`, `Log Data Module`, `Rich Editor Module`, `Select Module`, `Chat Belum Terbalas Notifier Module`, `Job Schedule Resource Module`, `Plan Task Dan Ticketing Module`, `Bug Analisis Duplicate Tchat Module`, `Date Range Component Module`, `Design Module`, `Select Module`, `Readme Module`, `Agents Module`?**
  _High betweenness centrality (0.132) - this node is a cross-community bridge._
- **Why does `h()` connect `Rich Editor Module` to `Code Editor Module`, `Proposal Module`, `Chart Module`, `Code Editor Module`, `Code Editor Module`, `Chart Module`, `Chart Module`, `Code Editor Module`, `Proposal Module`, `Markdown Editor Module`, `Chart Module`, `Rich Editor Module`, `Hak Akses Resource Module`, `Code Editor Module`, `Chart Module`, `Tables Module`, `Anggota Grup Whatsapp Module`, `Support Module`, `Chart Module`, `Chart Module`, `Markdown Editor Module`, `Chart Module`, `Markdown Editor Module`, `Code Editor Module`, `Rich Editor Module`, `Chart Module`, `Register Module`, `Slider Module`, `Spec Module`, `Code Editor Module`, `Date Range Component Module`, `Date Range Component Module`, `Vpoint Assistant Module`, `Select Module`, `Ai Agent Module`, `Support Module`, `Chart Module`, `Chart Module`, `Chart Module`, `Attachment Controller Module`, `Dashboard Module`, `Rich Editor Module`, `Support Module`, `Chart Module`?**
  _High betweenness centrality (0.068) - this node is a cross-community bridge._