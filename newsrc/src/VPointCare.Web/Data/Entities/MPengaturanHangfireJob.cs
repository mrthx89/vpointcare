using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MPengaturanHangfireJob")]
public class MPengaturanHangfireJob
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(100)]
    public string KodeJob { get; set; } = "";

    [StringLength(150)]
    public string NamaJob { get; set; } = "";

    [StringLength(150)]
    public string JobIdHangfire { get; set; } = "";

    [StringLength(100)]
    public string CronExpression { get; set; } = "";

    public bool Aktif { get; set; }

    [StringLength(500)]
    public string? Keterangan { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
