using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MKategoriTicket")]
public class MKategoriTicket
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(50)]
    public string KodeKategori { get; set; } = "";

    [StringLength(150)]
    public string NamaKategori { get; set; } = "";

    [StringLength(500)]
    public string? Keterangan { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual IEnumerable<TTicket>? Tickets { get; set; }
}
