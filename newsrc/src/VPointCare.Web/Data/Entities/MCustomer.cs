using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MCustomer")]
public class MCustomer
{
    [Key]
    public Guid Id { get; set; }

    public Guid? IdInstansi { get; set; }

    [StringLength(50)]
    public string KodeCustomer { get; set; } = "";

    [StringLength(200)]
    public string NamaCustomer { get; set; } = "";

    [StringLength(150)]
    public string? Email { get; set; }

    [StringLength(50)]
    public string? Telepon { get; set; }

    [StringLength(100)]
    public string? Jabatan { get; set; }

    [StringLength(1000)]
    public string? Catatan { get; set; }

    [StringLength(50)]
    public string? SumberData { get; set; }

    [StringLength(100)]
    public string? IdExternal { get; set; }

    public DateTime? TglSinkronTerakhir { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual MInstansi? Instansi { get; set; }
    public virtual IEnumerable<MNomorWhatsapp>? NomorWhatsapps { get; set; }
    public virtual IEnumerable<MAnggotaGrupWhatsapp>? AnggotaGrupWhatsapps { get; set; }
    public virtual IEnumerable<MProdukCustomer>? ProdukCustomers { get; set; }
    public virtual IEnumerable<TChat>? Chats { get; set; }
    public virtual IEnumerable<TTicket>? Tickets { get; set; }
}
