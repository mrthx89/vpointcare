using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MAiProvider")]
public class MAiProvider
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(50)]
    public string KodeProvider { get; set; } = "";

    [StringLength(100)]
    public string NamaProvider { get; set; } = "";

    [StringLength(255)]
    public string? BaseUrl { get; set; }

    [StringLength(1000)]
    public string? ApiKeyTerenkripsi { get; set; }

    [StringLength(100)]
    public string? ModelDefault { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual IEnumerable<TAiPermintaan>? AiPermintaan { get; set; }
}
