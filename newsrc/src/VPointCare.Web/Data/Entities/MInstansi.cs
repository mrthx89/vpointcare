using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MInstansi")]
public class MInstansi
{
    [Key]
    public Guid Id { get; set; }

    [StringLength(50)]
    public string KodeInstansi { get; set; } = "";

    [StringLength(200)]
    public string NamaInstansi { get; set; } = "";

    [StringLength(500)]
    public string? Alamat { get; set; }

    [StringLength(100)]
    public string? Kota { get; set; }

    [StringLength(100)]
    public string? Provinsi { get; set; }

    [StringLength(100)]
    public string? Negara { get; set; }

    [StringLength(20)]
    public string? KodePos { get; set; }

    [StringLength(50)]
    public string? Telepon { get; set; }

    [StringLength(150)]
    public string? Email { get; set; }

    [StringLength(200)]
    public string? Website { get; set; }

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

    public virtual IEnumerable<MCustomer>? Customers { get; set; }
    public virtual IEnumerable<MNomorWhatsapp>? NomorWhatsapps { get; set; }
    public virtual IEnumerable<MGrupWhatsapp>? GrupWhatsapps { get; set; }
    public virtual IEnumerable<MProdukCustomer>? ProdukCustomers { get; set; }
    public virtual IEnumerable<TChat>? Chats { get; set; }
    public virtual IEnumerable<TTicket>? Tickets { get; set; }
}
