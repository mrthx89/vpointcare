using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TAiRespon")]
public class TAiRespon
{
    [Key]
    public Guid Id { get; set; }

    public Guid IdAiPermintaan { get; set; }

    [StringLength(100)]
    public string JenisRespon { get; set; } = "";

    public string? ResponRingkas { get; set; }

    public string? ResponJson { get; set; }

    public int? TokenInput { get; set; }

    public int? TokenOutput { get; set; }

    public decimal? BiayaEstimasi { get; set; }

    public Guid? DisetujuiOleh { get; set; }

    public DateTime? TglDisetujui { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual TAiPermintaan? AiPermintaan { get; set; }
    public virtual IEnumerable<TChatD>? ChatD { get; set; }
}
