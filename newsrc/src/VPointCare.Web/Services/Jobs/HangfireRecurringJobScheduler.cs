using Hangfire;
using Microsoft.EntityFrameworkCore;
using VPointCare.Web.Data;
using VPointCare.Web.Jobs;

namespace VPointCare.Web.Services.Jobs;

public class HangfireRecurringJobScheduler(VPointCareDbContext dbContext, ILogger<HangfireRecurringJobScheduler> logger)
{
    private static readonly HangfireJobDefinition[] JobDefinitions =
    [
        new("VTOKEN_OPEN_CUSTOMERS_SYNC", "vtoken-open-customers-sync"),
        new("UNANSWERED_CHAT_NOTIFICATION", "unanswered-chat-notification"),
        new("AI_AUTO_REPLY", "ai-auto-reply")
    ];

    public async Task SyncAsync(CancellationToken cancellationToken = default)
    {
        var settings = await dbContext.MPengaturanHangfireJobSet
            .AsNoTracking()
            .Where(x => !x.NonAktif)
            .ToListAsync(cancellationToken);

        foreach (var definition in JobDefinitions)
        {
            var setting = settings.FirstOrDefault(x => x.KodeJob == definition.KodeJob);
            var jobId = setting?.JobIdHangfire;
            if (string.IsNullOrWhiteSpace(jobId))
            {
                jobId = definition.DefaultJobId;
            }

            if (setting is null)
            {
                RecurringJob.RemoveIfExists(jobId);
                logger.LogWarning("Setting Hangfire job {KodeJob} belum ada di database. Job {JobId} tidak dijadwalkan.", definition.KodeJob, jobId);
                continue;
            }

            if (!setting.Aktif)
            {
                RecurringJob.RemoveIfExists(jobId);
                logger.LogInformation("Hangfire job {KodeJob} dinonaktifkan dari database. JobId={JobId}", setting.KodeJob, jobId);
                continue;
            }

            if (string.IsNullOrWhiteSpace(setting.CronExpression))
            {
                RecurringJob.RemoveIfExists(jobId);
                logger.LogWarning("CronExpression Hangfire job {KodeJob} kosong. JobId={JobId} tidak dijadwalkan.", setting.KodeJob, jobId);
                continue;
            }

            try
            {
                Schedule(setting.KodeJob, jobId, setting.CronExpression);
                logger.LogInformation("Hangfire job {KodeJob} dijadwalkan. JobId={JobId}, Cron={CronExpression}", setting.KodeJob, jobId, setting.CronExpression);
            }
            catch (Exception ex)
            {
                logger.LogError(ex, "Gagal menjadwalkan Hangfire job {KodeJob}. JobId={JobId}, Cron={CronExpression}", setting.KodeJob, jobId, setting.CronExpression);
            }
        }
    }

    private static void Schedule(string kodeJob, string jobId, string cronExpression)
    {
        switch (kodeJob)
        {
            case "VTOKEN_OPEN_CUSTOMERS_SYNC":
                RecurringJob.AddOrUpdate<VTokenSyncJob>(jobId, job => job.ExecuteAsync(CancellationToken.None), cronExpression);
                break;
            case "UNANSWERED_CHAT_NOTIFICATION":
                RecurringJob.AddOrUpdate<UnansweredChatNotificationJob>(jobId, job => job.ExecuteAsync(CancellationToken.None), cronExpression);
                break;
            case "AI_AUTO_REPLY":
                RecurringJob.AddOrUpdate<AiAutoReplyJob>(jobId, job => job.ExecuteAsync(CancellationToken.None), cronExpression);
                break;
        }
    }

    private sealed record HangfireJobDefinition(string KodeJob, string DefaultJobId);
}
