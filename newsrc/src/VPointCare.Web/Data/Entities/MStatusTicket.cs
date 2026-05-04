using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MStatusTicket")]
public class MStatusTicket
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(50)]
    public string KodeStatusTicket { get; set; } = "";

    [StringLength(100)]
    public string NamaStatusTicket { get; set; } = "";

    public int Urutan { get; set; }

    public bool StatusFinal { get; set; }

    [StringLength(30)]
    public string? Warna { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
