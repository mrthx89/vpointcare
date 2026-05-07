using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.SignalR;
using VPointCare.Web.Services.Realtime;

namespace VPointCare.Web.Hubs;

[Authorize]
public class WahaInboxHub(ActiveAgentTracker activeAgentTracker) : Hub
{
    public const string InboxViewerGroup = "waha-inbox-viewers";

    public override async Task OnConnectedAsync()
    {
        activeAgentTracker.Connected(Context.ConnectionId, Context.User!);
        await Groups.AddToGroupAsync(Context.ConnectionId, InboxViewerGroup);
        await Clients.Group(InboxViewerGroup).SendAsync("AgentsUpdated", activeAgentTracker.Snapshot());
        await base.OnConnectedAsync();
    }

    public override async Task OnDisconnectedAsync(Exception? exception)
    {
        activeAgentTracker.Disconnected(Context.ConnectionId);
        await Clients.Group(InboxViewerGroup).SendAsync("AgentsUpdated", activeAgentTracker.Snapshot());
        await base.OnDisconnectedAsync(exception);
    }

    public async Task Heartbeat()
    {
        activeAgentTracker.Seen(Context.ConnectionId);
        await Clients.Group(InboxViewerGroup).SendAsync("AgentsUpdated", activeAgentTracker.Snapshot());
    }
}
