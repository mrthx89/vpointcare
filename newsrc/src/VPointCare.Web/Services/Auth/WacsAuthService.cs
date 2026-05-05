using System.Security.Claims;
using Microsoft.AspNetCore.Authentication;
using Microsoft.AspNetCore.Authentication.Cookies;
using Microsoft.EntityFrameworkCore;
using VPointCare.Web.Data;

namespace VPointCare.Web.Services.Auth;

public class WacsAuthService(VPointCareDbContext dbContext, IHttpContextAccessor httpContextAccessor)
{
    public async Task<LoginResult> SignInAsync(string email, string password, CancellationToken cancellationToken = default)
    {
        var normalizedEmail = email.Trim();
        var user = await dbContext.Users
            .AsNoTracking()
            .FirstOrDefaultAsync(x => x.Email == normalizedEmail, cancellationToken);

        if (user is null || !BCrypt.Net.BCrypt.Verify(password, user.Password))
        {
            return LoginResult.Failed("Email atau password tidak sesuai.");
        }

        if (!string.Equals(user.Status, "approved", StringComparison.OrdinalIgnoreCase))
        {
            return LoginResult.Failed("User belum aktif atau sedang diblokir.");
        }

        var pengguna = await dbContext.Penggunas
            .AsNoTracking()
            .FirstOrDefaultAsync(x => x.IdUser == user.Id || x.Email == normalizedEmail, cancellationToken);

        if (pengguna is not null && pengguna.NonAktif)
        {
            return LoginResult.Failed("Pengguna sedang nonaktif.");
        }

        var claims = new List<Claim>
        {
            new(ClaimTypes.NameIdentifier, user.Id.ToString()),
            new(ClaimTypes.Name, user.Name),
            new(ClaimTypes.Email, user.Email),
            new("muser_id", user.Id.ToString())
        };

        if (pengguna is not null)
        {
            claims.Add(new Claim("pengguna_id", pengguna.Id.ToString()));
            claims.Add(new Claim("pengguna_nama", pengguna.NamaPengguna));
            claims.Add(new Claim("peran_id", pengguna.IdPeran.ToString()));
        }

        var identity = new ClaimsIdentity(claims, CookieAuthenticationDefaults.AuthenticationScheme);
        var principal = new ClaimsPrincipal(identity);

        var context = httpContextAccessor.HttpContext;
        if (context is null)
        {
            return LoginResult.Failed("Konteks login tidak tersedia.");
        }

        await context.SignInAsync(CookieAuthenticationDefaults.AuthenticationScheme, principal);

        if (pengguna is not null)
        {
            await dbContext.Penggunas
                .Where(x => x.Id == pengguna.Id)
                .ExecuteUpdateAsync(setters => setters
                    .SetProperty(x => x.LoginTerakhirPada, DateTime.UtcNow)
                    .SetProperty(x => x.TglEdit, DateTime.UtcNow), cancellationToken);
        }

        return LoginResult.Success();
    }

    public async Task SignOutAsync()
    {
        var context = httpContextAccessor.HttpContext;
        if (context is not null)
        {
            await context.SignOutAsync(CookieAuthenticationDefaults.AuthenticationScheme);
        }
    }
}

public sealed record LoginResult(bool Ok, string? Message)
{
    public static LoginResult Success() => new(true, null);

    public static LoginResult Failed(string message) => new(false, message);
}
