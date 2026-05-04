using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MPeran")]
public class MPeran
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(50)]
    public string KodePeran { get; set; } = "";

    [StringLength(100)]
    public string NamaPeran { get; set; } = "";

    [StringLength(255)]
    public string? Keterangan { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
