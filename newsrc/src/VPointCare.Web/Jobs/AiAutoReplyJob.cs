using Microsoft.EntityFrameworkCore;
using VPointCare.Web.Data;
using VPointCare.Web.Services.Ai;

namespace VPointCare.Web.Jobs;

public class AiAutoReplyJob(VPointCareDbContext dbContext, AiAutoReplyService aiAutoReplyService, ILogger<AiAutoReplyJob> logger)
{
    public async Task<int> ExecuteAsync(CancellationToken cancellationToken = default)
    {
        var latestIncoming = dbContext.ChatDetails
            .Where(x => x.ArahPesan == "Masuk" && x.DikirimOlehCustomer && x.IsiPesan != null)
            .GroupBy(x => x.IdChatM)
            .Select(group => new { IdChatM = group.Key, TglPesanTerakhirMasuk = group.Max(x => x.TglPesan) });

        var latestAiReply = dbContext.ChatDetails
            .Where(x => x.ArahPesan == "Keluar" && x.DihasilkanOlehAi)
            .GroupBy(x => x.IdChatM)
            .Select(group => new { IdChatM = group.Key, TglPesanTerakhirAi = group.Max(x => x.TglPesan) });

        var chatIds = await (
            from incoming in latestIncoming
            join aiReply in latestAiReply on incoming.IdChatM equals aiReply.IdChatM into aiReplyJoin
            from aiReply in aiReplyJoin.DefaultIfEmpty()
            where aiReply == null || aiReply.TglPesanTerakhirAi < incoming.TglPesanTerakhirMasuk
            orderby incoming.TglPesanTerakhirMasuk
            select incoming.IdChatM)
            .Take(20)
            .ToListAsync(cancellationToken);

        var processed = 0;
        foreach (var chatId in chatIds)
        {
            var result = await aiAutoReplyService.HandleIncomingChatAsync(chatId, cancellationToken);
            if (result is { Ok: true, Skipped: false })
            {
                processed++;
            }
        }

        logger.LogInformation("AI auto reply Hangfire selesai. Diproses={Processed}, Kandidat={Candidates}", processed, chatIds.Count);
        return processed;
    }
}
