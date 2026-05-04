using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TLogIntegrasi")]
public class TLogIntegrasi
{
    [Key]
    public Guid Id { get; set; }

    public Guid? IdEndpointIntegrasi { get; set; }

    [StringLength(100)]
    public string KodeIntegrasi { get; set; } = "";

    [StringLength(500)]
    public string UrlEndpoint { get; set; } = "";

    [StringLength(10)]
    public string MetodeHttp { get; set; } = "";

    public string? RequestJson { get; set; }

    public string? ResponseJson { get; set; }

    public int? StatusHttp { get; set; }

    public bool Berhasil { get; set; }

    public string? PesanError { get; set; }

    public DateTime TglRequest { get; set; }

    public DateTime? TglResponse { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
