using System.Security.Claims;
using Microsoft.EntityFrameworkCore;
using VPointCare.Web.Data;

namespace VPointCare.Web.Services.Auth;

public class PermissionService(VPointCareDbContext dbContext)
{
    public async Task<HashSet<string>> GetPermissionCodesAsync(ClaimsPrincipal user, CancellationToken cancellationToken = default)
    {
        if (user.IsInRole("ADMIN") || string.Equals(user.FindFirst("peran_kode")?.Value, "ADMIN", StringComparison.OrdinalIgnoreCase))
        {
            return AppPermissions.All.ToHashSet(StringComparer.OrdinalIgnoreCase);
        }

        var roleIdText = user.FindFirst("peran_id")?.Value;
        if (!Guid.TryParse(roleIdText, out var roleId))
        {
            return new HashSet<string>(StringComparer.OrdinalIgnoreCase);
        }

        var codes = await (
            from roleAccess in dbContext.MPeranHakAksesSet.AsNoTracking()
            join access in dbContext.MHakAksesSet.AsNoTracking() on roleAccess.IdHakAkses equals access.Id
            where roleAccess.IdPeran == roleId
                && !roleAccess.NonAktif
                && !access.NonAktif
            select access.KodeHakAkses)
            .ToListAsync(cancellationToken);

        return codes.ToHashSet(StringComparer.OrdinalIgnoreCase);
    }

    public async Task<bool> HasPermissionAsync(ClaimsPrincipal user, string permissionCode, CancellationToken cancellationToken = default)
    {
        var permissions = await GetPermissionCodesAsync(user, cancellationToken);
        return permissions.Contains(permissionCode);
    }
}
