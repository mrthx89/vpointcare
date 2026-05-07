using Microsoft.EntityFrameworkCore;
using VPointCare.Web.Data;

namespace VPointCare.Web.Services.Dashboard;

public class DashboardQueryService(VPointCareDbContext dbContext)
{
    public async Task<DashboardSnapshot> GetSnapshotAsync(CancellationToken cancellationToken = default)
    {
        var today = DateTime.UtcNow.Date;
        var chats = dbContext.TChatSet.AsNoTracking();
        var messages = dbContext.TChatDSet.AsNoTracking();

        var totalChats = await chats.CountAsync(cancellationToken);
        var unread = await chats.SumAsync(x => (int?)x.JumlahPesanBelumDibaca, cancellationToken) ?? 0;
        var groupChats = await chats.CountAsync(x => x.JenisChat == "Grup", cancellationToken);
        var incomingToday = await messages.CountAsync(x => x.DikirimOlehCustomer && x.TglPesan >= today, cancellationToken);
        var aiReplies = await messages.CountAsync(x => x.DihasilkanOlehAi, cancellationToken);
        var activeAiChats = await chats.CountAsync(x => x.AutoReplyAiAktif, cancellationToken);

        return new DashboardSnapshot(totalChats, unread, groupChats, incomingToday, activeAiChats, aiReplies);
    }
}

public sealed record DashboardSnapshot(int TotalChats, int UnreadMessages, int GroupChats, int IncomingToday, int ActiveAiChats, int AiReplies);
