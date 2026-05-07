using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TLogWebhookWaha")]
public class TLogWebhookWaha
{
    [Key]
    public Guid Id { get; set; }

    public Guid? IdSesiWhatsapp { get; set; }

    [StringLength(100)]
    public string JenisEvent { get; set; } = "";

    public string PayloadJson { get; set; } = "";

    public DateTime TglDiterima { get; set; }

    public bool SudahDiproses { get; set; }

    public DateTime? TglDiproses { get; set; }

    public string? PesanError { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual MSesiWhatsapp? SesiWhatsapp { get; set; }
    public virtual IEnumerable<TChatD>? ChatDs { get; set; }
}
