namespace VPointCare.Web.Services.Realtime;

public sealed record InboxUpdatedPayload(Guid ChatId, string? NomorWhatsapp, string? NamaKontak, string? IsiPesan, DateTime TglPesan);

public sealed record NewMessageNotificationPayload(Guid ChatId, string Title, string Body, DateTime TglPesan);

public sealed record ActiveAgentPayload(long UserId, Guid? PenggunaId, string Name, string? Email, DateTime ConnectedAt, DateTime LastSeenAt);

public sealed record AgentsUpdatedPayload(int ActiveCount, IReadOnlyCollection<ActiveAgentPayload> Agents);
