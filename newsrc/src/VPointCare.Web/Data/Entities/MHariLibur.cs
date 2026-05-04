using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MHariLibur")]
public class MHariLibur
{
    [Key]
    public Guid Id { get; set; }

    public DateTime TanggalLibur { get; set; }

    [StringLength(200)]
    public string NamaHariLibur { get; set; } = "";

    [StringLength(1000)]
    public string? Keterangan { get; set; }

    public bool BerlakuTahunan { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
