using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MStatusChat")]
public class MStatusChat
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(50)]
    public string KodeStatusChat { get; set; } = "";

    [StringLength(100)]
    public string NamaStatusChat { get; set; } = "";

    public int Urutan { get; set; }

    [StringLength(30)]
    public string? Warna { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual IEnumerable<TChat>? Chats { get; set; }
}
