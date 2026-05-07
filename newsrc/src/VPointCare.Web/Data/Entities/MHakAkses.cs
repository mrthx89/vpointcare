using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MHakAkses")]
public class MHakAkses
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(100)]
    public string KodeHakAkses { get; set; } = "";

    [StringLength(150)]
    public string NamaHakAkses { get; set; } = "";

    [StringLength(100)]
    public string Modul { get; set; } = "";

    [StringLength(255)]
    public string? Keterangan { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual IEnumerable<MPeranHakAkses>? PeranHakAkses { get; set; }
}
