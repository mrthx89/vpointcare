using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TChatDPenugasan")]
public class TChatDPenugasan
{
    [Key]
    public Guid Id { get; set; }

    public Guid IdChat { get; set; }

    public Guid? DitugaskanDari { get; set; }

    public Guid DitugaskanKepada { get; set; }

    [StringLength(500)]
    public string? AlasanPenugasan { get; set; }

    public DateTime TglPenugasan { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual TChat? Chat { get; set; }
}
