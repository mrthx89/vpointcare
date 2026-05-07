using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MPengaturanAi")]
public class MPengaturanAi
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(50)]
    public string KodePengaturan { get; set; } = "";

    [StringLength(100)]
    public string NamaPengaturan { get; set; } = "";

    public bool AutoReplyAktif { get; set; }

    public bool AutoReplyDiluarJamKerja { get; set; }

    public bool AutoReplyHariLibur { get; set; }

    public bool AutoReplyJamKerjaSapaan { get; set; }

    public bool AutoReplyJamKerjaBerlanjut { get; set; }

    public TimeSpan JamKerjaMulai { get; set; }

    public TimeSpan JamKerjaSelesai { get; set; }

    [StringLength(50)]
    public string HariKerja { get; set; } = "";

    [StringLength(100)]
    public string ZonaWaktu { get; set; } = "";

    [StringLength(50)]
    public string ProviderAi { get; set; } = "";

    [StringLength(100)]
    public string? ModelAi { get; set; }

    [StringLength(255)]
    public string? BaseUrl { get; set; }

    public string? ApiKeyTerenkripsi { get; set; }

    public string? PromptSistem { get; set; }

    public string? TemplateDiluarJamKerja { get; set; }

    public string? TemplateHariLibur { get; set; }

    public string? TemplateJamKerjaSapaan { get; set; }

    public string? TemplateFallback { get; set; }

    public bool NotifikasiChatBelumTerbalasAktif { get; set; }

    public int MenitTungguNotifikasi { get; set; }

    public int JedaNotifikasiMenit { get; set; }

    [StringLength(200)]
    public string KodePeranPenerimaNotifikasi { get; set; } = "";

    public string? TemplateNotifikasiChatBelumTerbalas { get; set; }

    public int BatasRiwayatPesan { get; set; }

    public bool KirimKeWaha { get; set; }

    [StringLength(50)]
    public string ModeKirim { get; set; } = "";

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
