using Microsoft.AspNetCore.Components.Routing;
using MudBlazor;

namespace VPointCare.Web.Services.Auth;

public static class AppMenuDefinition
{
    public static readonly IReadOnlyList<AppMenuGroup> Groups =
    [
        new("Utama", Icons.Material.Filled.SpaceDashboard,
        [
            new("Dashboard", "/admin", Icons.Material.Filled.SpaceDashboard, AppPermissions.MenuDashboard, NavLinkMatch.All)
        ]),
        new("Operasional", Icons.Material.Filled.SupportAgent,
        [
            new("Inbox WhatsApp", "/admin/inbox-whatsapp", Icons.Material.Filled.MarkUnreadChatAlt, AppPermissions.MenuInboxWhatsapp),
            new("Ticketing", "/admin/ticketing", Icons.Material.Filled.ConfirmationNumber, AppPermissions.MenuTicketing)
        ]),
        new("Asisten AI", Icons.Material.Filled.SmartToy,
        [
            new("AI Agent", "/admin/ai-agent", Icons.Material.Filled.SmartToy, AppPermissions.MenuAiAgent, NavLinkMatch.All),
            new("Knowledge Base AI", "/admin/ai-agent/knowledge-base", Icons.Material.Filled.Book, AppPermissions.MenuKnowledgeBaseAi),
            new("Hari Libur", "/admin/master-customer/hari-libur", Icons.Material.Filled.EventBusy, AppPermissions.MenuHariLibur)
        ]),
        new("Master Data", Icons.Material.Filled.Dataset,
        [
            new("Ringkasan Customer", "/admin/master-customer", Icons.Material.Filled.Business, AppPermissions.MenuMasterCustomer, NavLinkMatch.All),
            new("Klien / Instansi", "/admin/master-customer/instansi", Icons.Material.Filled.Apartment, AppPermissions.MenuInstansi),
            new("Kontak Customer", "/admin/master-customer/customers", Icons.Material.Filled.Groups, AppPermissions.MenuCustomer),
            new("Nomor WhatsApp", "/admin/master-customer/nomor-whatsapp", Icons.Material.Filled.PhoneIphone, AppPermissions.MenuNomorWhatsapp),
            new("Grup WhatsApp", "/admin/master-customer/grup-whatsapp", Icons.Material.Filled.Forum, AppPermissions.MenuGrupWhatsapp),
            new("Anggota Grup", "/admin/master-customer/anggota-grup", Icons.Material.Filled.GroupAdd, AppPermissions.MenuAnggotaGrup)
        ]),
        new("Monitoring", Icons.Material.Filled.MonitorHeart,
        [
            new("Log Data", "/admin/log-data", Icons.Material.Filled.Article, AppPermissions.MenuLogData),
            new("Hangfire Jobs", "/admin/jobs", Icons.Material.Filled.WorkHistory, AppPermissions.MenuHangfireJobs)
        ]),
        new("Pengaturan", Icons.Material.Filled.Settings,
        [
            new("Pengaturan Jobs", "/admin/pengaturan/jobs", Icons.Material.Filled.SettingsSuggest, AppPermissions.MenuPengaturanJobs),
            new("MUser", "/admin/users", Icons.Material.Filled.ManageAccounts, AppPermissions.MenuUsers, NavLinkMatch.All),
            new("Pengguna Internal", "/admin/users/pengguna", Icons.Material.Filled.PeopleAlt, AppPermissions.MenuPenggunaInternal)
        ])
    ];
}

public sealed record AppMenuGroup(string Title, string Icon, IReadOnlyList<AppMenuItem> Items);

public sealed record AppMenuItem(
    string Title,
    string Href,
    string Icon,
    string PermissionCode,
    NavLinkMatch Match = NavLinkMatch.Prefix);
