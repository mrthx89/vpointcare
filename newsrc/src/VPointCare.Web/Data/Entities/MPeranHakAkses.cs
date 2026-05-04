using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MPeranHakAkses")]
public class MPeranHakAkses
{
    [Key]
    public Guid Id { get; set; }

    public Guid IdPeran { get; set; }

    public Guid IdHakAkses { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
