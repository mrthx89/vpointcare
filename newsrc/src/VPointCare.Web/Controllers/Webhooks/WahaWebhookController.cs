using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using Microsoft.AspNetCore.Mvc;
using VPointCare.Web.Services.Ai;
using VPointCare.Web.Services.Waha;

namespace VPointCare.Web.Controllers.Webhooks;

[ApiController]
[Route("webhooks/waha/{token?}")]
public class WahaWebhookController(IConfiguration configuration, WahaWebhookProcessor processor, AiAutoReplyService autoReply) : ControllerBase
{
    [HttpPost]
    public async Task<IActionResult> Receive([FromRoute] string? token, CancellationToken cancellationToken)
    {
        var expectedToken = configuration["Waha:WebhookToken"];
        if (!string.IsNullOrWhiteSpace(expectedToken) && !string.Equals(expectedToken, token, StringComparison.Ordinal))
        {
            return Unauthorized(new { ok = false, message = "Invalid webhook token." });
        }

        using var reader = new StreamReader(Request.Body, Encoding.UTF8);
        var body = await reader.ReadToEndAsync(cancellationToken);

        if (!ValidateHmac(body))
        {
            return Unauthorized(new { ok = false, message = "Invalid webhook signature." });
        }

        using var document = JsonDocument.Parse(body);
        var result = await processor.ProcessAsync(document.RootElement.Clone(), cancellationToken);
        AiAutoReplyResult? autoReplyResult = null;
        if (result.Ok && !result.Ignored && !result.Duplicate && result.ChatId is not null)
        {
            try
            {
                autoReplyResult = await autoReply.HandleIncomingChatAsync(result.ChatId.Value, cancellationToken);
            }
            catch
            {
                autoReplyResult = new AiAutoReplyResult(false, false, "AI auto reply gagal, webhook tetap diterima.", null, null, null);
            }
        }

        return Ok(new
        {
            ok = result.Ok,
            webhook_id = result.WebhookId,
            chat_id = result.ChatId,
            ignored = result.Ignored,
            duplicate = result.Duplicate,
            jenis_chat = result.ChatType,
            message = result.Message,
            auto_reply = autoReplyResult
        });
    }

    private bool ValidateHmac(string body)
    {
        var secret = configuration["Waha:WebhookHmacSecret"];
        if (string.IsNullOrWhiteSpace(secret))
        {
            return true;
        }

        var header = Request.Headers["X-Webhook-Hmac"].FirstOrDefault();
        if (string.IsNullOrWhiteSpace(header))
        {
            return false;
        }

        using var hmac = new HMACSHA512(Encoding.UTF8.GetBytes(secret));
        var hash = Convert.ToHexString(hmac.ComputeHash(Encoding.UTF8.GetBytes(body))).ToLowerInvariant();
        return CryptographicOperations.FixedTimeEquals(Encoding.UTF8.GetBytes(hash), Encoding.UTF8.GetBytes(header.ToLowerInvariant()));
    }
}
