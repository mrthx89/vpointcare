namespace VPointCare.Web.Services.Auth;

public static class AppPermissions
{
    public const string MenuDashboard = "MENU_DASHBOARD";
    public const string MenuInboxWhatsapp = "MENU_INBOX_WHATSAPP";
    public const string MenuTicketing = "MENU_TICKETING";
    public const string MenuAiAgent = "MENU_AI_AGENT";
    public const string MenuKnowledgeBaseAi = "MENU_KNOWLEDGE_BASE_AI";
    public const string MenuHariLibur = "MENU_HARI_LIBUR";
    public const string MenuMasterCustomer = "MENU_MASTER_CUSTOMER";
    public const string MenuInstansi = "MENU_INSTANSI";
    public const string MenuCustomer = "MENU_CUSTOMER";
    public const string MenuNomorWhatsapp = "MENU_NOMOR_WHATSAPP";
    public const string MenuGrupWhatsapp = "MENU_GRUP_WHATSAPP";
    public const string MenuAnggotaGrup = "MENU_ANGGOTA_GRUP";
    public const string MenuLogData = "MENU_LOG_DATA";
    public const string MenuHangfireJobs = "MENU_HANGFIRE_JOBS";
    public const string MenuPengaturanJobs = "MENU_PENGATURAN_JOBS";
    public const string MenuUsers = "MENU_USERS";
    public const string MenuPenggunaInternal = "MENU_PENGGUNA_INTERNAL";

    public static readonly string[] All =
    [
        MenuDashboard,
        MenuInboxWhatsapp,
        MenuTicketing,
        MenuAiAgent,
        MenuKnowledgeBaseAi,
        MenuHariLibur,
        MenuMasterCustomer,
        MenuInstansi,
        MenuCustomer,
        MenuNomorWhatsapp,
        MenuGrupWhatsapp,
        MenuAnggotaGrup,
        MenuLogData,
        MenuHangfireJobs,
        MenuPengaturanJobs,
        MenuUsers,
        MenuPenggunaInternal
    ];
}
