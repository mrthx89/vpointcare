using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("TChatCatatanInternal")]
public class TChatCatatanInternal
{
    [Key]
    public Guid Id { get; set; }

    public Guid IdChatM { get; set; }

    public string IsiCatatan { get; set; } = "";

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }
}
