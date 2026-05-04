using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TTicketD")]
public class TTicketD
{
    [Key]
    public Guid Id { get; set; }

    public Guid IdTicketM { get; set; }

    [StringLength(100)]
    public string JenisAktivitas { get; set; } = "";

    public string? IsiAktivitas { get; set; }

    [StringLength(100)]
    public string? StatusSebelum { get; set; }

    [StringLength(100)]
    public string? StatusSesudah { get; set; }

    public Guid? DitujukanKepada { get; set; }

    public DateTime TglAktivitas { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
