using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TChatDCatatanInternal")]
public class TChatDCatatanInternal
{
    [Key]
    public Guid Id { get; set; }

    public Guid IdChat { get; set; }

    public string IsiCatatan { get; set; } = "";

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual TChat? Chat { get; set; }
}
