using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MEndpointIntegrasi")]
public class MEndpointIntegrasi
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(100)]
    public string KodeEndpoint { get; set; } = "";

    [StringLength(150)]
    public string NamaEndpoint { get; set; } = "";

    [StringLength(500)]
    public string UrlEndpoint { get; set; } = "";

    [StringLength(10)]
    public string MetodeHttp { get; set; } = "";

    public string? HeaderJson { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual IEnumerable<TLogIntegrasi>? LogIntegrasis { get; set; }
}
