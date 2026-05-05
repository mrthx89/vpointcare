using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TChat")]
public class TChat
{
    [Key]
    public Guid Id { get; set; }

    public Guid IdSesiWhatsapp { get; set; }
    public virtual MSesiWhatsapp? SesiWhatsapp { get; set; }

    public Guid? IdStatusChat { get; set; }
    public virtual MStatusChat? StatusChat { get; set; }

    public Guid? IdCustomer { get; set; }
    public virtual MCustomer? Customer { get; set; }

    public Guid? IdInstansi { get; set; }
    public virtual MInstansi? Instansi { get; set; }

    public Guid? IdNomorWhatsapp { get; set; }
    public virtual MNomorWhatsapp? NomorWhatsappMaster { get; set; }

    public Guid? IdGrupWhatsapp { get; set; }
    public virtual MGrupWhatsapp? GrupWhatsapp { get; set; }

    [StringLength(30)]
    public string JenisChat { get; set; } = "";

    [StringLength(30)]
    public string NomorWhatsapp { get; set; } = "";

    [StringLength(150)]
    public string? NamaKontak { get; set; }

    [StringLength(200)]
    public string? NamaGrupWhatsapp { get; set; }

    [StringLength(200)]
    public string? IdWahaTerdeteksi { get; set; }

    [StringLength(30)]
    public string? NomorWhatsappTerdeteksi { get; set; }

    [StringLength(1000)]
    public string? UrlFotoProfil { get; set; }

    public DateTime? TglFotoProfilDiambil { get; set; }

    [StringLength(50)]
    public string Prioritas { get; set; } = "";

    public Guid? DitugaskanKepada { get; set; }

    public Guid? DiambilOleh { get; set; }

    public DateTime? TglDiambil { get; set; }

    public DateTime? TglChatTerakhir { get; set; }

    public DateTime? TglDibalasTerakhir { get; set; }

    public int JumlahPesanBelumDibaca { get; set; }

    public Guid? DitutupOleh { get; set; }

    public DateTime? TglDitutup { get; set; }

    public string? RingkasanAi { get; set; }

    public bool AutoReplyAiAktif { get; set; }

    public bool AiSudahMenyapa { get; set; }

    [StringLength(50)]
    public string ModeAutoReplyAi { get; set; } = "";

    public DateTime? TglAutoReplyAiTerakhir { get; set; }

    public DateTime? TglNotifikasiBelumTerbalasTerakhir { get; set; }

    public int JumlahNotifikasiBelumTerbalas { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual IEnumerable<TChatD>? ChatD { get; set; }
    public virtual IEnumerable<TChatDCatatanInternal>? ChatDCatatanInternal { get; set; }
    public virtual IEnumerable<TChatDPenugasan>? ChatDPenugasan { get; set; }
    public virtual IEnumerable<TTicket>? Tickets { get; set; }
    public virtual IEnumerable<TAiPermintaan>? AiPermintaan { get; set; }
}
