using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TChatD")]
public class TChatD
{
    [Key]
    public Guid Id { get; set; }

    public Guid IdChatM { get; set; }

    public Guid? IdLogWebhookWaha { get; set; }

    [StringLength(200)]
    public string? IdPesanWaha { get; set; }

    [StringLength(20)]
    public string ArahPesan { get; set; } = "";

    [StringLength(50)]
    public string JenisPesan { get; set; } = "";

    public string? IsiPesan { get; set; }

    [StringLength(1000)]
    public string? UrlMedia { get; set; }

    [StringLength(255)]
    public string? NamaFileMedia { get; set; }

    [StringLength(100)]
    public string? TipeMime { get; set; }

    public string? PayloadJson { get; set; }

    [StringLength(30)]
    public string? PengirimNomorWhatsapp { get; set; }

    [StringLength(150)]
    public string? PengirimNamaKontak { get; set; }

    public bool DikirimOlehCustomer { get; set; }

    public bool DihasilkanOlehAi { get; set; }

    public Guid? IdAiRespon { get; set; }

    public Guid? DibalasOleh { get; set; }

    public DateTime TglPesan { get; set; }

    public DateTime? TglDikirim { get; set; }

    public DateTime? TglDibaca { get; set; }

    [StringLength(50)]
    public string? StatusKirim { get; set; }

    public string? PesanError { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
