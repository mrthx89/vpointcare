using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MGrupWhatsapp")]
public class MGrupWhatsapp
{
    [Key]
    public Guid Id { get; set; }

    public Guid IdInstansi { get; set; }

    [StringLength(50)]
    public string KodeGrup { get; set; } = "";

    [StringLength(200)]
    public string NamaGrup { get; set; } = "";

    [StringLength(200)]
    public string? IdGrupWaha { get; set; }

    [StringLength(100)]
    public string? NomorGrupWhatsapp { get; set; }

    [StringLength(500)]
    public string? Deskripsi { get; set; }

    [StringLength(50)]
    public string? SumberData { get; set; }

    [StringLength(100)]
    public string? IdExternal { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual MInstansi? Instansi { get; set; }
    public virtual IEnumerable<MAnggotaGrupWhatsapp>? AnggotaGrupWhatsapps { get; set; }
    public virtual IEnumerable<TChat>? Chats { get; set; }
}
