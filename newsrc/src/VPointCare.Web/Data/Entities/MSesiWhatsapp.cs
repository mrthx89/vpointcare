using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MSesiWhatsapp")]
public class MSesiWhatsapp
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(50)]
    public string KodeSesi { get; set; } = "";

    [StringLength(150)]
    public string NamaSesi { get; set; } = "";

    [StringLength(255)]
    public string BaseUrlWaha { get; set; } = "";

    [StringLength(255)]
    public string? ApiKey { get; set; }

    [StringLength(30)]
    public string? NomorTerhubung { get; set; }

    [StringLength(50)]
    public string StatusSesi { get; set; } = "";

    [StringLength(255)]
    public string? WebhookToken { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual IEnumerable<TChat>? Chats { get; set; }
    public virtual IEnumerable<TLogWebhookWaha>? LogWebhookWahas { get; set; }
}
