using System.Collections.Concurrent;
using System.Security.Claims;

namespace VPointCare.Web.Services.Realtime;

public class ActiveAgentTracker
{
    private readonly ConcurrentDictionary<string, AgentConnection> _connections = new();

    public void Connected(string connectionId, ClaimsPrincipal principal)
    {
        var userId = ParseLong(principal.FindFirstValue("muser_id") ?? principal.FindFirstValue(ClaimTypes.NameIdentifier));
        if (userId is null)
        {
            return;
        }

        _connections[connectionId] = new AgentConnection(
            connectionId,
            userId.Value,
            ParseGuid(principal.FindFirstValue("pengguna_id")),
            principal.FindFirstValue("pengguna_nama") ?? principal.Identity?.Name ?? "Agent",
            principal.FindFirstValue(ClaimTypes.Email),
            DateTime.UtcNow,
            DateTime.UtcNow);
    }

    public void Seen(string connectionId)
    {
        if (_connections.TryGetValue(connectionId, out var connection))
        {
            _connections[connectionId] = connection with { LastSeenAt = DateTime.UtcNow };
        }
    }

    public void Disconnected(string connectionId)
    {
        _connections.TryRemove(connectionId, out _);
    }

    public AgentsUpdatedPayload Snapshot()
    {
        var agents = _connections.Values
            .GroupBy(x => x.UserId)
            .Select(group =>
            {
                var latest = group.OrderByDescending(x => x.LastSeenAt).First();
                return new ActiveAgentPayload(latest.UserId, latest.PenggunaId, latest.Name, latest.Email, group.Min(x => x.ConnectedAt), latest.LastSeenAt);
            })
            .OrderBy(x => x.Name)
            .ToArray();

        return new AgentsUpdatedPayload(agents.Length, agents);
    }

    private static long? ParseLong(string? value) => long.TryParse(value, out var parsed) ? parsed : null;

    private static Guid? ParseGuid(string? value) => Guid.TryParse(value, out var parsed) ? parsed : null;

    private sealed record AgentConnection(string ConnectionId, long UserId, Guid? PenggunaId, string Name, string? Email, DateTime ConnectedAt, DateTime LastSeenAt);
}
