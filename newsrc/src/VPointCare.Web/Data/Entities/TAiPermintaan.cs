using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TAiPermintaan")]
public class TAiPermintaan
{
    [Key]
    public Guid Id { get; set; }

    public Guid? IdAiProvider { get; set; }

    [StringLength(100)]
    public string JenisPermintaan { get; set; } = "";

    [StringLength(50)]
    public string ProviderAi { get; set; } = "";

    [StringLength(100)]
    public string? ModelAi { get; set; }

    public Guid? IdChatM { get; set; }

    public Guid? IdTicketM { get; set; }

    public string? PromptRingkas { get; set; }

    public string? PromptJson { get; set; }

    [StringLength(50)]
    public string StatusPermintaan { get; set; } = "";

    public DateTime? TglMulai { get; set; }

    public DateTime? TglSelesai { get; set; }

    public string? PesanError { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
