using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MPengetahuan")]
public class MPengetahuan
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(50)]
    public string KodePengetahuan { get; set; } = "";

    [StringLength(200)]
    public string JudulPengetahuan { get; set; } = "";

    public string IsiPengetahuan { get; set; } = "";

    [StringLength(500)]
    public string? Tag { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
