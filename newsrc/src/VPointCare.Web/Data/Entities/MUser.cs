using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MUser")]
public class MUser
{
    [Key]
    public long Id { get; set; }

    [StringLength(255)]
    public string Name { get; set; } = "";

    [StringLength(255)]
    public string Email { get; set; } = "";

    [Column("email_verified_at")]
    public DateTime? EmailVerifiedAt { get; set; }

    [StringLength(255)]
    public string Password { get; set; } = "";

    [Column("remember_token")]
    [StringLength(100)]
    public string? RememberToken { get; set; }

    [StringLength(20)]
    public string Status { get; set; } = "";

    [Column("approved_at")]
    public DateTime? ApprovedAt { get; set; }

    [Column("blocked_at")]
    public DateTime? BlockedAt { get; set; }

    [Column("created_at")]
    public DateTime? CreatedAt { get; set; }

    [Column("updated_at")]
    public DateTime? UpdatedAt { get; set; }

    public virtual IEnumerable<MPengguna>? Pengguna { get; set; }
}
