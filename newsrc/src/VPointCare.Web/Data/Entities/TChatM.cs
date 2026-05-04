using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TChatM")]
public class TChatM
{
    [Key]
    public Guid Id { get; set; }

    public Guid IdSesiWhatsapp { get; set; }

    public Guid? IdStatusChat { get; set; }

    public Guid? IdCustomer { get; set; }

    public Guid? IdInstansi { get; set; }

    public Guid? IdNomorWhatsapp { get; set; }

    public Guid? IdGrupWhatsapp { get; set; }

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
}
