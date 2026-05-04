using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TLogError")]
public class TLogError
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(50)]
    public string LevelError { get; set; } = "";

    public string PesanError { get; set; } = "";

    [StringLength(500)]
    public string? FileError { get; set; }

    public int? BarisError { get; set; }

    public string? StackTrace { get; set; }

    public string? ContextJson { get; set; }

    public DateTime TglError { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
