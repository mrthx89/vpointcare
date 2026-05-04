using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MPengguna")]
public class MPengguna
{
    [Key]
    public Guid Id { get; set; }

    public long? UserId { get; set; }

    public Guid IdPeran { get; set; }

    [StringLength(150)]
    public string NamaPengguna { get; set; } = "";

    [StringLength(150)]
    public string Email { get; set; } = "";

    [StringLength(255)]
    public string Password { get; set; } = "";

    [StringLength(30)]
    public string? NomorWhatsappInternal { get; set; }

    [StringLength(500)]
    public string? FotoProfilPath { get; set; }

    [StringLength(100)]
    public string? Jabatan { get; set; }

    [StringLength(100)]
    public string? RememberToken { get; set; }

    public DateTime? EmailTerverifikasiPada { get; set; }

    public DateTime? LoginTerakhirPada { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
