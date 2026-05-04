using Hangfire;
using Hangfire.SqlServer;
using Microsoft.AspNetCore.Authentication.Cookies;
using Microsoft.EntityFrameworkCore;
using MudBlazor.Services;
using Serilog;
using VPointCare.Web.Components;
using VPointCare.Web.Data;
using VPointCare.Web.Hubs;
using VPointCare.Web.Jobs;
using VPointCare.Web.Services.Ai;
using VPointCare.Web.Services.Auth;
using VPointCare.Web.Services.Dashboard;
using VPointCare.Web.Services.Realtime;
using VPointCare.Web.Services.Waha;

var builder = WebApplication.CreateBuilder(args);

builder.Host.UseSerilog((context, services, configuration) => configuration
    .ReadFrom.Configuration(context.Configuration)
    .ReadFrom.Services(services)
    .Enrich.FromLogContext());

builder.Services.AddRazorComponents()
    .AddInteractiveServerComponents();

builder.Services.AddControllers();
builder.Services.AddHttpContextAccessor();
builder.Services.AddMudServices();
builder.Services.AddSignalR();
builder.Services.AddCascadingAuthenticationState();

builder.Services.AddAuthentication(CookieAuthenticationDefaults.AuthenticationScheme)
    .AddCookie(options =>
    {
        options.Cookie.Name = "VPointCare.Auth";
        options.LoginPath = "/login";
        options.LogoutPath = "/logout";
        options.AccessDeniedPath = "/login";
        options.SlidingExpiration = true;
    });
builder.Services.AddAuthorization();

builder.Services.AddDbContext<VPointCareDbContext>(options =>
{
    options.UseSqlServer(builder.Configuration.GetConnectionString("WacsDb"));
});

builder.Services.AddSingleton<ActiveAgentTracker>();
builder.Services.AddScoped<WacsAuthService>();
builder.Services.AddScoped<WahaWebhookProcessor>();
builder.Services.AddScoped<DashboardQueryService>();
builder.Services.AddHttpClient<VTokenSyncJob>();
builder.Services.AddHttpClient<WahaSenderService>();
builder.Services.AddHttpClient<AiAutoReplyService>();
builder.Services.AddScoped<UnansweredChatNotificationJob>();
builder.Services.AddScoped<AiAutoReplyJob>();

var hangfireConnection = builder.Configuration.GetConnectionString("Hangfire");
var hangfireEnabled = !string.IsNullOrWhiteSpace(hangfireConnection);
if (hangfireEnabled)
{
    builder.Services.AddHangfire(configuration => configuration
        .UseSimpleAssemblyNameTypeSerializer()
        .UseRecommendedSerializerSettings()
        .UseSqlServerStorage(hangfireConnection, new SqlServerStorageOptions
        {
            CommandBatchMaxTimeout = TimeSpan.FromMinutes(5),
            SlidingInvisibilityTimeout = TimeSpan.FromMinutes(5),
            QueuePollInterval = TimeSpan.FromSeconds(15),
            UseRecommendedIsolationLevel = true,
            DisableGlobalLocks = true
        }));
    builder.Services.AddHangfireServer();
}

var app = builder.Build();

if (!app.Environment.IsDevelopment())
{
    app.UseExceptionHandler("/Error", createScopeForErrors: true);
    app.UseHsts();
}

app.UseHttpsRedirection();

app.UseStaticFiles();
app.UseAntiforgery();
app.UseAuthentication();
app.UseAuthorization();

app.MapControllers();
app.MapHub<WahaInboxHub>("/hubs/waha-inbox");

app.MapRazorComponents<App>()
    .AddInteractiveServerRenderMode();

if (hangfireEnabled)
{
    app.UseHangfireDashboard("/admin/jobs");
    RecurringJob.AddOrUpdate<VTokenSyncJob>("vtoken-open-customers-sync", job => job.ExecuteAsync(CancellationToken.None), Cron.Hourly);
    RecurringJob.AddOrUpdate<UnansweredChatNotificationJob>("unanswered-chat-notification", job => job.ExecuteAsync(CancellationToken.None), "*/5 * * * *");
    RecurringJob.AddOrUpdate<AiAutoReplyJob>("ai-auto-reply", job => job.ExecuteAsync(CancellationToken.None), "*/2 * * * *");
}

app.Run();
