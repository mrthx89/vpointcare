using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace VPointCare.Web.Data.Entities;

[Table("MAnggotaGrupWhatsapp")]
public class MAnggotaGrupWhatsapp
{
    [Key]
    public Guid Id { get; set; }

    public Guid IdGrupWhatsapp { get; set; }

    public Guid IdNomorWhatsapp { get; set; }

    public Guid? IdCustomer { get; set; }

    [StringLength(100)]
    public string? PeranAnggota { get; set; }

    public bool NonAktif { get; set; }

    public DateTime TglBuat { get; set; }

    public Guid? DibuatOleh { get; set; }

    public DateTime? TglEdit { get; set; }

    public Guid? DieditOleh { get; set; }

    public virtual MGrupWhatsapp? GrupWhatsapp { get; set; }
    public virtual MCustomer? Customer { get; set; }
    public virtual MNomorWhatsapp? NomorWhatsapp { get; set; }
}
