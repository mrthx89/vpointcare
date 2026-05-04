using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TLogAktivitas")]
public class TLogAktivitas
{
    [Key]
    public Guid Id { get; set; }

    public Guid? IdPengguna { get; set; }

    [StringLength(100)]
    public string Modul { get; set; } = "";

    [StringLength(100)]
    public string Aksi { get; set; } = "";

    [StringLength(1000)]
    public string? Keterangan { get; set; }

    [StringLength(50)]
    public string? IpAddress { get; set; }

    [StringLength(500)]
    public string? UserAgent { get; set; }

    public string? DataSebelumJson { get; set; }

    public string? DataSesudahJson { get; set; }

    public DateTime TglAktivitas { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
