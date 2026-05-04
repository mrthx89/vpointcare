using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TTicketPenugasan")]
public class TTicketPenugasan
{
    [Key]
    public Guid Id { get; set; }

    public Guid IdTicketM { get; set; }

    public Guid? DitugaskanDari { get; set; }

    public Guid DitugaskanKepada { get; set; }

    [StringLength(500)]
    public string? AlasanPenugasan { get; set; }

    public DateTime TglPenugasan { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
