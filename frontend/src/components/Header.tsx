import React from 'react'

export const Header = ({firstname}: { firstname: string }) => {
  return (
    <div className='header'>
      <h2 className='text-xl font-semibold'>Bienvenue {firstname}</h2>
    </div>
  )
}
