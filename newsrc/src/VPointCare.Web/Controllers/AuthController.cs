using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using VPointCare.Web.Services.Auth;

namespace VPointCare.Web.Controllers;

[Route("")]
public class AuthController(WacsAuthService authService) : Controller
{
    [HttpPost("auth/login")]
    [AllowAnonymous]
    public async Task<IActionResult> Login([FromForm] string email, [FromForm] string password, [FromForm] string? returnUrl, CancellationToken cancellationToken)
    {
        var result = await authService.SignInAsync(email, password, cancellationToken);
        if (!result.Ok)
        {
            return Redirect($"/login?error={Uri.EscapeDataString(result.Message ?? "Login gagal.")}&returnUrl={Uri.EscapeDataString(returnUrl ?? "/admin")}");
        }

        return LocalRedirect(SafeReturnUrl(returnUrl));
    }

    [HttpGet("logout")]
    public async Task<IActionResult> Logout()
    {
        await authService.SignOutAsync();
        return LocalRedirect("/login");
    }

    private static string SafeReturnUrl(string? returnUrl)
    {
        if (string.IsNullOrWhiteSpace(returnUrl) || !returnUrl.StartsWith('/'))
        {
            return "/admin";
        }

        return returnUrl;
    }
}
